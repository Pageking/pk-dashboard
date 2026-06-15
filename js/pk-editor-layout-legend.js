// Sidebar-legenda met links naar ACF flex-content rijen. Kleuren via window.pkLayoutLegend (wp_localize_script).
(function () {
    const METABOX_ID = 'pk-page-layout-legend';
    const LEGEND_ID  = 'pk-layout-legend';

    let frame = null; // rAF debounce handle

    function getLayouts() {
        return [...document.querySelectorAll('.acf-field-flexible-content .layout:not(.acf-clone)')];
    }

    // Leest de zichtbare titel uit de layout-header; valt terug op de layout-slug.
    function getTitle(layout) {
        const titleNode = layout.querySelector('.acf-fc-layout-title');
        if (!titleNode) return layout.dataset.layout || '';

        const label = titleNode.querySelector('.layout-title');
        if (label) return label.textContent.trim();

        return [...titleNode.childNodes]
            .filter(n => n.nodeType === Node.TEXT_NODE && n.textContent.trim())
            .map(n => n.textContent.trim())
            .join(' ') || layout.dataset.layout || '';
    }

    const SNIPPET_FIELDS = ['titel', 'heading', 'kop', 'text', 'tekst'];

    // Zet een korte inhoudspreview onder elke layouttitel zodat rijen herkenbaar zijn ingeklapt.
    function annotateLayouts(layouts) {
        layouts.forEach(layout => {
            const titleNode = layout.querySelector('.acf-fc-layout-title');
            if (!titleNode) return;

            let snippet = '';
            for (const field of layout.querySelectorAll('.acf-field')) {
                if (field.closest('.layout') !== layout) continue;
                const labelText = field.querySelector('.acf-label label')?.textContent.trim().toLowerCase() || '';
                if (!SNIPPET_FIELDS.includes(labelText)) continue;
                const value = field.querySelector('input[type="text"], textarea')?.value.trim() || '';
                if (value) { snippet = value; break; }
            }

            let el = titleNode.querySelector('.pk-layout-card-heading');
            if (snippet) {
                if (!el) {
                    el = document.createElement('div');
                    el.className = 'pk-layout-card-heading';
                    titleNode.appendChild(el);
                }
                el.textContent = snippet;
            } else {
                el?.remove();
            }
        });
    }

    function render() {
        const legend = document.getElementById(LEGEND_ID);
        if (!legend) return;

        legend.innerHTML = '';
        const layouts = getLayouts();

        if (!layouts.length) {
            legend.textContent = 'Geen rijen gevonden.';
            return;
        }

        annotateLayouts(layouts);

        const { layoutCats = {}, colors = {} } = window.pkLayoutLegend || {};

        const list = document.createElement('ol');
        list.className = 'pk-layout-legend-list';

        layouts.forEach((layout, i) => {
            const slug  = layoutCats[layout.dataset.layout || ''] || '';
            const color = colors[slug] || '';

            const li  = document.createElement('li');
            const btn = document.createElement('button');
            btn.type        = 'button';
            btn.className   = 'pk-layout-legend-item';
            btn.textContent = getTitle(layout) || `Rij ${i + 1}`;
            btn.onclick = () => {
                // Klap alle andere rijen in, open deze rij en scroll ernaar.
                getLayouts().forEach(other => {
                    if (other !== layout && !other.classList.contains('-collapsed')) {
                        other.querySelector('[data-name="collapse-layout"]')?.click();
                    }
                });
                if (layout.classList.contains('-collapsed')) {
                    layout.querySelector('[data-name="collapse-layout"]')?.click();
                }
                setTimeout(() => layout.scrollIntoView({ behavior: 'smooth', block: 'start' }), 100);
            };

            if (color) li.style.setProperty('--pk-layout-color', color);
            li.appendChild(btn);
            list.appendChild(li);
        });

        legend.appendChild(list);

        // Categorieoverzicht (gededupliceerd).
        const seen = {};
        layouts.forEach(layout => {
            const slug = layoutCats[layout.dataset.layout || ''] || '';
            if (slug && !seen[slug]) seen[slug] = colors[slug] || '';
        });

        const cats = Object.entries(seen);
        if (!cats.length) return;

        const catList = document.createElement('ul');
        catList.className = 'pk-layout-legend-categories';

        cats.forEach(([slug, color]) => {
            const li    = document.createElement('li');
            const dot   = document.createElement('span');
            const label = document.createElement('span');
            li.className    = 'pk-layout-legend-category';
            dot.className   = 'pk-layout-legend-category-dot';
            label.className = 'pk-layout-legend-category-label';
            label.textContent = slug[0].toUpperCase() + slug.slice(1);
            if (color) {
                li.style.setProperty('--pk-layout-color', color);
                dot.style.background = color;
            }
            li.append(dot, label);
            catList.appendChild(li);
        });

        legend.appendChild(catList);
    }

    // Verplaatst de metabox naar direct onder #pageparentdiv in de zijbalk.
    function moveMetabox() {
        const box  = document.getElementById(METABOX_ID);
        const attr = document.getElementById('pageparentdiv');
        if (box && attr && attr.parentNode === box.parentNode) {
            attr.insertAdjacentElement('afterend', box);
        }
    }

    // Bundelt renders via rAF zodat snel opeenvolgende events één herbouw geven.
    function scheduleRender() {
        if (frame) cancelAnimationFrame(frame);
        frame = requestAnimationFrame(() => { frame = null; moveMetabox(); render(); });
    }

    function init() {
        if (!document.getElementById(LEGEND_ID)) return;

        document.addEventListener('input', scheduleRender);
        document.addEventListener('change', scheduleRender);
        window.addEventListener('load', scheduleRender);

        // ACF-knoppen muteren de DOM asynchroon; 120ms wachten voor de herquery.
        document.addEventListener('click', e => {
            if (e.target.closest('.acf-actions a, .acf-fc-layout-controls a, .acf-icon.-plus, .acf-icon.-minus')) {
                setTimeout(scheduleRender, 120);
            }
        });

        if (window.acf?.addAction) {
            acf.addAction('ready', scheduleRender);
            acf.addAction('append', scheduleRender);
            acf.addAction('remove', scheduleRender);
            acf.addAction('sortstop', scheduleRender);
        }

        scheduleRender();
    }

    document.addEventListener('DOMContentLoaded', init);
}());
