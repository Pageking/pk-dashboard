(function () {
	const legendNodeId = 'pk-layout-legend';
	const metaBoxId = 'pk-page-layout-legend';
	const debugNodeId = 'pk-layout-legend-debug';
	const rowLabel = window.pkLayoutLegend && window.pkLayoutLegend.labels ? window.pkLayoutLegend.labels.row : 'Rij';
	const rowsActiveLabel = window.pkLayoutLegend && window.pkLayoutLegend.labels ? window.pkLayoutLegend.labels.rowsActive : 'rijen actief';
	const emptyLabel = window.pkLayoutLegend && window.pkLayoutLegend.labels ? window.pkLayoutLegend.labels.empty : 'Nog geen rijen gevonden in deze contentopbouw.';
	const moveUpLabel = window.pkLayoutLegend && window.pkLayoutLegend.labels ? window.pkLayoutLegend.labels.moveUp : 'Omhoog';
	const moveDownLabel = window.pkLayoutLegend && window.pkLayoutLegend.labels ? window.pkLayoutLegend.labels.moveDown : 'Omlaag';
	const openLabel = window.pkLayoutLegend && window.pkLayoutLegend.labels ? window.pkLayoutLegend.labels.open : 'Open';

	let renderFrame = null;
	let currentRows = [];
	let currentRowMap = {};
	let currentLayoutMap = {};

	function normalizeText(value) {
		return value ? value.replace(/\s+/g, ' ').trim() : '';
	}

	function createNode(tagName, className, text) {
		const node = document.createElement(tagName);
		if (className) {
			node.className = className;
		}
		if (typeof text === 'string') {
			node.textContent = text;
		}
		return node;
	}

	function getLegendNode() {
		return document.getElementById(legendNodeId);
	}

	function getDebugNode() {
		return document.getElementById(debugNodeId);
	}

	function setDebugMessage(message, tone) {
		const debugNode = getDebugNode();
		if (!debugNode) {
			return;
		}

		debugNode.textContent = message;
		debugNode.dataset.tone = tone || 'muted';
	}

	function rebuildMaps(rows) {
		currentRows = rows;
		currentRowMap = {};
		currentLayoutMap = {};

		rows.forEach(function (rowData) {
			currentRowMap[rowData.key] = rowData;
			rowData.layouts.forEach(function (layoutData) {
				currentLayoutMap[layoutData.key] = layoutData;
			});
		});
	}

	function moveMetaboxBelowPageAttributes() {
		const metaBox = document.getElementById(metaBoxId);
		const pageAttributes = document.getElementById('pageparentdiv');

		if (!metaBox || !pageAttributes || !pageAttributes.parentNode || pageAttributes.parentNode !== metaBox.parentNode) {
			return;
		}

		pageAttributes.insertAdjacentElement('afterend', metaBox);
	}

	function getToneIndex(value) {
		const input = value || '';
		let hash = 0;

		for (let index = 0; index < input.length; index += 1) {
			hash = ((hash << 5) - hash) + input.charCodeAt(index);
			hash |= 0;
		}

		return Math.abs(hash) % 6;
	}

	function getFieldLabel(field) {
		const labelNode = field.querySelector('.acf-label label') || field.querySelector('.acf-label');
		return normalizeText(labelNode ? labelNode.textContent : '');
	}

	function getFieldValue(field) {
		const candidates = field.querySelectorAll('input[type="text"], input[type="url"], textarea, select');

		for (let index = 0; index < candidates.length; index += 1) {
			const input = candidates[index];
			const parentField = input.closest('.acf-field');
			if (parentField !== field) {
				continue;
			}

			let value = '';
			if (input.tagName === 'SELECT') {
				value = input.selectedOptions && input.selectedOptions.length ? input.selectedOptions[0].textContent : input.value;
			} else {
				value = input.value;
			}

			value = normalizeText(value);
			if (value) {
				return value;
			}
		}

		return '';
	}

	function getFieldData(scope, matcher) {
		return Array.prototype.slice.call(scope.querySelectorAll('.acf-field')).filter(function (field) {
			return !field.classList.contains('acf-clone') && matcher(field);
		}).map(function (field) {
			return {
				label: getFieldLabel(field),
				value: getFieldValue(field)
			};
		}).filter(function (field) {
			return field.label && field.value;
		});
	}

	function getBestFieldValue(fields, patterns) {
		for (let fieldIndex = 0; fieldIndex < fields.length; fieldIndex += 1) {
			const currentField = fields[fieldIndex];
			const label = currentField.label.toLowerCase();

			for (let patternIndex = 0; patternIndex < patterns.length; patternIndex += 1) {
				if (patterns[patternIndex].test(label)) {
					return currentField.value;
				}
			}
		}

		return '';
	}

	function getLayoutTitle(layout) {
		const selectors = [
			'.acf-fc-layout-title .layout-title',
			'.acf-fc-layout-handle',
			'.acf-fc-layout-title'
		];

		for (let index = 0; index < selectors.length; index += 1) {
			const node = layout.querySelector(selectors[index]);
			const text = normalizeText(node ? node.textContent : '');
			if (text) {
				return text;
			}
		}

		return '';
	}

	function getLayoutKey(layout, index, row) {
		const rowBaseKey = row ? (row.getAttribute('data-id') || row.dataset.id || row.dataset.key || row.getAttribute('data-row') || 'row') : 'row';
		const layoutBaseKey = layout.getAttribute('data-id') || layout.dataset.id || layout.dataset.key || layout.getAttribute('data-layout') || 'layout';
		const compositeKey = rowBaseKey + '-' + layoutBaseKey + '-' + String(index + 1);
		return 'pk-layout-' + compositeKey.replace(/[^a-z0-9_-]+/gi, '-').toLowerCase();
	}

	function getRowKey(row, index) {
		const baseKey = row.getAttribute('data-id') || row.dataset.id || row.dataset.key || row.getAttribute('data-row') || String(index + 1);
		return 'pk-row-' + baseKey.replace(/[^a-z0-9_-]+/gi, '-').toLowerCase();
	}

	function findEditorRowContainer(flexibleField) {
		let current = flexibleField ? flexibleField.parentElement : null;

		while (current) {
			if (current.classList && current.classList.contains('acf-row')) {
				const ownsFlexibleField = current.querySelector('.acf-field-flexible-content') === flexibleField;
				const hasRowIdField = !!current.querySelector('.acf-field[data-name="id"], .acf-field[data-key*="id"]');
				if (ownsFlexibleField || hasRowIdField) {
					return current;
				}
			}

			current = current.parentElement;
		}

		return flexibleField ? flexibleField.closest('.acf-row') : null;
	}

	function getContentRows() {
		const flexibleFields = Array.prototype.slice.call(document.querySelectorAll('.acf-field-flexible-content')).filter(function (field) {
			return field.querySelector('.layout:not(.acf-clone)');
		});

		const seen = new Set();
		const rows = [];

		flexibleFields.forEach(function (field) {
			const row = findEditorRowContainer(field);
			if (!row || row.classList.contains('acf-clone')) {
				return;
			}

			if (seen.has(row)) {
				return;
			}

			seen.add(row);
			rows.push(row);
		});

		return rows;
	}

	function getRowLayouts(row) {
		const flexibleField = row.querySelector('.acf-field-flexible-content');
		if (!flexibleField) {
			return [];
		}

		const layoutsContainer = flexibleField.querySelector('.values');
		const layouts = layoutsContainer ? layoutsContainer.children : [];

		return Array.prototype.slice.call(layouts).filter(function (layout) {
			return layout.classList && layout.classList.contains('layout') && !layout.classList.contains('acf-clone');
		});
	}

	function getLayoutData(layout, index, row) {
		const fields = getFieldData(layout, function (field) {
			return field.closest('.layout') === layout;
		});
		const header = getBestFieldValue(fields, [/^titel$/, /\btitel\b/, /\btitle\b/, /\bkop\b/, /\bheading\b/, /\bheader\b/, /\bheadline\b/, /^h[1-6]\b/]);
		const layoutTitle = getLayoutTitle(layout);
		const tone = getToneIndex(layoutTitle || header || String(index + 1));

		return {
			index: index + 1,
			key: getLayoutKey(layout, index, row),
			layout: layout,
			row: row,
			layoutTitle: layoutTitle,
			header: header,
			tone: tone
		};
	}

	function getRowData(row, index) {
		const fields = getFieldData(row, function (field) {
			return field.closest('.acf-row') === row && !field.closest('.layout');
		});
		const rowId = getBestFieldValue(fields, [/^id$/, /\brow id\b/, /\banchor\b/, /\banker\b/, /\bslug\b/]);
		const layouts = getRowLayouts(row).map(function (layout, layoutIndex) {
			return getLayoutData(layout, layoutIndex, row);
		});

		return {
			index: index + 1,
			key: getRowKey(row, index),
			row: row,
			rowId: rowId,
			layouts: layouts
		};
	}

	function getDisplayHeader(layoutData) {
		if (!layoutData.header) {
			return '';
		}

		if (normalizeText(layoutData.header).toLowerCase() === normalizeText(layoutData.layoutTitle).toLowerCase()) {
			return '';
		}

		return layoutData.header;
	}

	function annotateLayoutCard(layoutData) {
		const titleNode = layoutData.layout.querySelector('.acf-fc-layout-title');
		if (!titleNode) {
			return;
		}

		let metaNode = titleNode.querySelector('.pk-layout-card-meta');
		if (!metaNode) {
			metaNode = createNode('div', 'pk-layout-card-meta');
			const titleLabel = titleNode.querySelector('.layout-title');
			if (titleLabel) {
				titleLabel.insertAdjacentElement('afterend', metaNode);
			} else {
				titleNode.appendChild(metaNode);
			}
		}

		titleNode.classList.remove('pk-tone-0', 'pk-tone-1', 'pk-tone-2', 'pk-tone-3', 'pk-tone-4', 'pk-tone-5');
		titleNode.classList.add('pk-tone-' + layoutData.tone);
		layoutData.layout.classList.remove('pk-tone-0', 'pk-tone-1', 'pk-tone-2', 'pk-tone-3', 'pk-tone-4', 'pk-tone-5');
		layoutData.layout.classList.add('pk-tone-' + layoutData.tone);

		metaNode.innerHTML = '';

		const displayHeader = getDisplayHeader(layoutData);
		if (displayHeader) {
			metaNode.appendChild(createNode('div', 'pk-layout-card-heading', displayHeader));
		}
	}

	function scrollToElement(element) {
		if (!element) {
			return;
		}

		element.scrollIntoView({ behavior: 'smooth', block: 'center' });
	}

	function forceOpenLayout(layout) {
		if (!layout) {
			return;
		}

		layout.classList.add('active-layout');
		layout.classList.remove('collapsed', '-collapsed', 'acf-collapsed');
		layout.setAttribute('aria-expanded', 'true');

		const contentNodes = layout.querySelectorAll('.acf-fc-layout-content, .acf-fields, .acf-table, .acf-input > .acf-flexible-content');
		Array.prototype.slice.call(contentNodes).forEach(function (node) {
			node.hidden = false;
			node.setAttribute('aria-hidden', 'false');
			node.style.display = '';
			if (window.jQuery) {
				window.jQuery(node).stop(true, true).show();
			}
		});

		const collapseButton = layout.querySelector('.acf-fc-layout-controls .-collapse');
		if (collapseButton) {
			collapseButton.classList.remove('-open');
		}
	}

	function openLayout(layoutData) {
		if (!layoutData || !layoutData.layout) {
			setDebugMessage('Openen mislukt: layout niet gevonden.', 'error');
			return;
		}

		const row = layoutData.row || findEditorRowContainer(layoutData.layout.closest('.acf-field-flexible-content'));
		const layoutHandle = layoutData.layout.querySelector('.acf-fc-layout-handle, .acf-fc-layout-title, .acf-fc-layout-actions-wrap');
		scrollToElement(layoutData.layout);

		if (!layoutData.layout.classList.contains('active-layout') && layoutHandle) {
			['mousedown', 'mouseup', 'click'].forEach(function (eventName) {
				layoutHandle.dispatchEvent(new MouseEvent(eventName, { bubbles: true, cancelable: true }));
			});
			if (window.jQuery) {
				window.jQuery(layoutHandle).trigger('mousedown').trigger('mouseup').trigger('click');
			}
		}

		forceOpenLayout(layoutData.layout);
		setDebugMessage('Layout geopend: ' + (layoutData.layoutTitle || 'zonder titel'), 'success');

		window.setTimeout(function () {
			if (row) {
				scrollToElement(row);
			}
			forceOpenLayout(layoutData.layout);
			scrollToElement(layoutData.layout);
		}, 220);
	}

	function moveRow(rowData, direction) {
		if (!rowData || !rowData.row || !rowData.row.parentNode) {
			setDebugMessage('Verplaatsen mislukt: rij niet gevonden.', 'error');
			return;
		}

		const siblings = Array.prototype.slice.call(rowData.row.parentNode.children).filter(function (child) {
			return child.classList && child.classList.contains('acf-row');
		});
		const currentIndex = siblings.indexOf(rowData.row);
		if (currentIndex === -1) {
			setDebugMessage('Verplaatsen mislukt: rijpositie onbekend.', 'error');
			return;
		}

		const targetIndex = currentIndex + direction;
		if (targetIndex < 0 || targetIndex >= siblings.length) {
			setDebugMessage(direction < 0 ? 'Rij staat al bovenaan.' : 'Rij staat al onderaan.', 'muted');
			return;
		}

		const targetRow = siblings[targetIndex];

		if (window.jQuery) {
			const $row = window.jQuery(rowData.row);
			if (direction < 0) {
				$row.insertBefore(targetRow);
			} else {
				$row.insertAfter(targetRow);
			}
		} else {
			if (direction < 0) {
				rowData.row.parentNode.insertBefore(rowData.row, targetRow);
			} else {
				rowData.row.parentNode.insertBefore(targetRow, rowData.row);
			}
		}

		setDebugMessage((direction < 0 ? 'Rij omhoog verplaatst: ' : 'Rij omlaag verplaatst: ') + rowData.index, 'success');
		window.setTimeout(scheduleRender, 80);
	}

	function createRowActions(rowData) {
		const actionsNode = createNode('div', 'pk-layout-legend-actions');

		const moveUpButton = createNode('button', 'pk-layout-legend-action pk-layout-legend-action--move');
		moveUpButton.type = 'button';
		moveUpButton.dataset.action = 'move-row';
		moveUpButton.dataset.direction = '-1';
		moveUpButton.dataset.rowKey = rowData.key;
		moveUpButton.setAttribute('aria-label', moveUpLabel + ' ' + rowLabel + ' ' + rowData.index);
		moveUpButton.title = moveUpLabel;
		moveUpButton.textContent = '↑';

		const moveDownButton = createNode('button', 'pk-layout-legend-action pk-layout-legend-action--move');
		moveDownButton.type = 'button';
		moveDownButton.dataset.action = 'move-row';
		moveDownButton.dataset.direction = '1';
		moveDownButton.dataset.rowKey = rowData.key;
		moveDownButton.setAttribute('aria-label', moveDownLabel + ' ' + rowLabel + ' ' + rowData.index);
		moveDownButton.title = moveDownLabel;
		moveDownButton.textContent = '↓';

		actionsNode.appendChild(moveUpButton);
		actionsNode.appendChild(moveDownButton);

		return actionsNode;
	}

	function renderLegend(rows) {
		const legendNode = getLegendNode();
		if (!legendNode) {
			return;
		}

		legendNode.innerHTML = '';

		if (!rows.length) {
			legendNode.appendChild(createNode('div', 'pk-layout-legend-empty', emptyLabel));
			return;
		}

		legendNode.appendChild(createNode('div', 'pk-layout-legend-summary', rows.length + ' ' + rowsActiveLabel));

		const listNode = createNode('ol', 'pk-layout-legend-list');

		rows.forEach(function (rowData) {
			const rowNode = createNode('li', 'pk-layout-legend-row');
			rowNode.dataset.rowKey = rowData.key;

			const rowHeader = createNode('div', 'pk-layout-legend-row-header');
			rowHeader.appendChild(createNode('span', 'pk-layout-legend-index', String(rowData.index)));

			const rowBody = createNode('div', 'pk-layout-legend-row-body');
			const topRowNode = createNode('div', 'pk-layout-legend-toprow');
			if (rowData.rowId) {
				topRowNode.appendChild(createNode('div', 'pk-layout-legend-name', rowData.rowId));
			}
			topRowNode.appendChild(createRowActions(rowData));
			rowBody.appendChild(topRowNode);
			rowHeader.appendChild(rowBody);
			rowNode.appendChild(rowHeader);

			const childList = createNode('ol', 'pk-layout-legend-children');

			rowData.layouts.forEach(function (layoutData) {
				const layoutNode = createNode('li', 'pk-layout-legend-child');
				layoutNode.classList.add('pk-tone-' + layoutData.tone);
				const layoutText = createNode('button', 'pk-layout-legend-child-main');
				layoutText.type = 'button';
				layoutText.dataset.action = 'open-layout';
				layoutText.dataset.layoutKey = layoutData.key;

				layoutText.appendChild(createNode('div', 'pk-layout-legend-layout', layoutData.layoutTitle || '-'));

				const displayHeader = getDisplayHeader(layoutData);
				if (displayHeader) {
					layoutText.appendChild(createNode('div', 'pk-layout-legend-heading', displayHeader));
				}

				const openButton = createNode('button', 'pk-layout-legend-action pk-layout-legend-action--open');
				openButton.type = 'button';
				openButton.dataset.action = 'open-layout';
				openButton.dataset.layoutKey = layoutData.key;
				openButton.setAttribute('aria-label', openLabel + ' ' + (layoutData.layoutTitle || '-'));
				openButton.title = openLabel;
				openButton.textContent = '→';

				layoutNode.appendChild(layoutText);
				layoutNode.appendChild(openButton);
				childList.appendChild(layoutNode);
			});

			rowNode.appendChild(childList);
			listNode.appendChild(rowNode);
		});

		legendNode.appendChild(listNode);
	}

	function render() {
		moveMetaboxBelowPageAttributes();

		const rows = getContentRows().map(function (row, index) {
			return getRowData(row, index);
		});
		rebuildMaps(rows);

		rows.forEach(function (rowData) {
			rowData.layouts.forEach(annotateLayoutCard);
		});

		renderLegend(rows);
		if (!getDebugNode().textContent) {
			setDebugMessage('Legenda actief.', 'muted');
		}
	}

	function scheduleRender() {
		if (renderFrame) {
			window.cancelAnimationFrame(renderFrame);
		}

		renderFrame = window.requestAnimationFrame(function () {
			renderFrame = null;
			render();
		});
	}

	function bindEvents() {
		document.addEventListener('input', scheduleRender);
		document.addEventListener('change', scheduleRender);
		window.addEventListener('load', scheduleRender);
		document.addEventListener('click', function (event) {
			if (event.target.closest('.acf-actions a, .acf-fc-layout-controls a, .acf-icon.-plus, .acf-icon.-minus')) {
				window.setTimeout(scheduleRender, 120);
			}
		});

		const legendNode = getLegendNode();
		if (legendNode) {
			legendNode.addEventListener('click', function (event) {
				const actionNode = event.target.closest('[data-action]');
				if (!actionNode) {
					return;
				}

				event.preventDefault();
				event.stopPropagation();

				if (actionNode.dataset.action === 'open-layout') {
					const layoutData = currentLayoutMap[actionNode.dataset.layoutKey];
					if (layoutData) {
						openLayout(layoutData);
					}
				}

				if (actionNode.dataset.action === 'move-row') {
					const rowData = currentRowMap[actionNode.dataset.rowKey];
					const direction = parseInt(actionNode.dataset.direction || '0', 10);
					if (rowData && direction) {
						moveRow(rowData, direction);
					}
				}
			});
		}

		if (window.acf && typeof window.acf.addAction === 'function') {
			window.acf.addAction('append', scheduleRender);
			window.acf.addAction('remove', scheduleRender);
			window.acf.addAction('sortstop', scheduleRender);
			window.acf.addAction('show_field', scheduleRender);
			window.acf.addAction('hide_field', scheduleRender);
			window.acf.addAction('unload', scheduleRender);
		}
	}

	function init() {
		if (!getLegendNode()) {
			return;
		}

		bindEvents();
		scheduleRender();
	}

	document.addEventListener('DOMContentLoaded', init);
}());