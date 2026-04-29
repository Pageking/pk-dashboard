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
	const toneCount = 15;
	const toneClassNames = Array.from({ length: toneCount }, function (_, index) {
		return 'pk-tone-' + index;
	});

	let renderFrame = null;
	let currentRows = [];
	let currentRowMap = {};
	let currentLayoutMap = {};
	const preservedLayoutParts = new WeakMap();

	function normalizeText(value) {
		return value ? value.replace(/\s+/g, ' ').trim() : '';
	}

	function normalizeIdentifier(value) {
		return normalizeText(value).toLowerCase().replace(/[^a-z0-9]+/g, '');
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

		return Math.abs(hash) % toneCount;
	}

	function getLayoutToneSeed(layout) {
		if (!layout) {
			return '';
		}

		const originalTitleNode = layout.querySelector('.acf-fc-layout-original-title');
		const originalTitle = normalizeIdentifier(originalTitleNode ? originalTitleNode.textContent : '');
		if (originalTitle) {
			return originalTitle;
		}

		const layoutNameInput = layout.querySelector('input[type="hidden"][name$=\"[acf_fc_layout]\"]');
		const layoutName = normalizeIdentifier(layoutNameInput ? layoutNameInput.value : '');
		if (layoutName) {
			return layoutName;
		}

		const layoutAttribute = normalizeIdentifier(layout.getAttribute('data-layout') || layout.dataset.layout || layout.dataset.id || '');
		if (layoutAttribute) {
			return layoutAttribute;
		}

		return normalizeIdentifier(getLayoutTitle(layout));
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
		const titleNode = layout.querySelector('.acf-fc-layout-title');
		if (!titleNode) {
			return '';
		}

		const titleLabel = titleNode.querySelector('.layout-title');
		if (titleLabel) {
			return normalizeText(titleLabel.textContent);
		}

		return getPlainLayoutTitleText(titleNode);
	}

	function getPlainLayoutTitleText(titleNode) {
		if (!titleNode) {
			return '';
		}

		const directText = Array.prototype.slice.call(titleNode.childNodes).map(function (node) {
			return node.nodeType === Node.TEXT_NODE ? node.textContent : '';
		}).join(' ');
		const normalizedDirectText = normalizeText(directText);

		if (normalizedDirectText) {
			return normalizedDirectText;
		}

		const clonedTitleNode = titleNode.cloneNode(true);
		Array.prototype.slice.call(clonedTitleNode.children).forEach(function (childNode) {
			clonedTitleNode.removeChild(childNode);
		});

		return normalizeText(clonedTitleNode.textContent);
	}

	function getPreservedLayoutParts(layout) {
		if (!layout) {
			return {};
		}

		if (!preservedLayoutParts.has(layout)) {
			preservedLayoutParts.set(layout, {});
		}

		return preservedLayoutParts.get(layout);
	}

	function preserveLayoutParts(titleNode, layout) {
		if (!titleNode || !layout) {
			return;
		}

		const parts = getPreservedLayoutParts(layout);
		const thumbnailNode = titleNode.querySelector('.layout-thumbnail');
		const metaNode = titleNode.querySelector('.pk-layout-card-meta');

		if (hasUsableThumbnailNode(thumbnailNode)) {
			parts.thumbnail = thumbnailNode.cloneNode(true);
		} else {
			const originalTitleThumbnail = findOriginalTitleThumbnailNode(titleNode);
			if (originalTitleThumbnail) {
				parts.thumbnail = originalTitleThumbnail;
			}
		}

		if (metaNode) {
			parts.meta = metaNode.cloneNode(true);
		}
	}

	function hasUsableThumbnailNode(node) {
		if (!node) {
			return false;
		}

		if (node.matches && node.matches('img')) {
			return !!node.getAttribute('src');
		}

		if (node.querySelector && node.querySelector('img[src]')) {
			return true;
		}

		const backgroundNode = node.querySelector ? node.querySelector('[style*="background-image"]') : null;
		return !!(backgroundNode && backgroundNode.style && backgroundNode.style.backgroundImage && backgroundNode.style.backgroundImage !== 'none');
	}

	function decodeOriginalTitleMarkup(titleNode) {
		if (!titleNode) {
			return null;
		}

		const titleContainer = titleNode.closest('.acf-fc-layout-handle') || titleNode.parentElement;
		const originalTitleNode = titleContainer ? titleContainer.querySelector('.acf-fc-layout-original-title') : null;
		const originalTitleHtml = originalTitleNode ? originalTitleNode.innerHTML : '';
		if (!originalTitleHtml || originalTitleHtml.indexOf('layout-thumbnail') === -1) {
			return null;
		}

		const parserNode = createNode('div');
		parserNode.innerHTML = originalTitleHtml;
		return parserNode;
	}

	function cloneThumbnailFromContainer(container) {
		if (!container || !container.querySelector) {
			return null;
		}

		const thumbnailNode = container.querySelector('.layout-thumbnail');
		if (thumbnailNode) {
			return thumbnailNode.cloneNode(true);
		}

		const imageNode = container.querySelector('img');
		if (imageNode) {
			const wrappedImage = createNode('span', 'layout-thumbnail');
			wrappedImage.appendChild(imageNode.cloneNode(true));
			return wrappedImage;
		}

		const backgroundNode = container.querySelector('[style*="background-image"]');
		if (backgroundNode && backgroundNode.style && backgroundNode.style.backgroundImage && backgroundNode.style.backgroundImage !== 'none') {
			const wrappedBackground = createNode('span', 'layout-thumbnail');
			const previewNode = createNode('span', 'layout-thumbnail__image');
			previewNode.style.backgroundImage = backgroundNode.style.backgroundImage;
			previewNode.style.backgroundPosition = backgroundNode.style.backgroundPosition || 'center';
			previewNode.style.backgroundRepeat = backgroundNode.style.backgroundRepeat || 'no-repeat';
			previewNode.style.backgroundSize = backgroundNode.style.backgroundSize || 'cover';
			previewNode.style.display = 'block';
			previewNode.style.width = '112px';
			previewNode.style.aspectRatio = '4 / 2.6';
			previewNode.style.borderRadius = '10px';
			previewNode.style.border = '1px solid #eaecf0';
			previewNode.style.boxShadow = '0 6px 18px rgba(16, 24, 40, 0.08)';
			wrappedBackground.appendChild(previewNode);
			return wrappedBackground;
		}

		return null;
	}

	function findOriginalTitleThumbnailNode(titleNode) {
		const decodedOriginalTitleNode = decodeOriginalTitleMarkup(titleNode);
		if (!decodedOriginalTitleNode) {
			return null;
		}

		return cloneThumbnailFromContainer(decodedOriginalTitleNode);
	}

	function findSiblingThumbnailNode(layout) {
		if (!layout) {
			return null;
		}

		const layoutType = getLayoutToneSeed(layout);
		if (!layoutType) {
			return null;
		}

		const siblingLayouts = document.querySelectorAll('.acf-field-flexible-content .layout:not(.acf-clone)');

		for (let index = 0; index < siblingLayouts.length; index += 1) {
			const siblingLayout = siblingLayouts[index];
			if (siblingLayout === layout || getLayoutToneSeed(siblingLayout) !== layoutType) {
				continue;
			}

			const siblingThumbnail = cloneThumbnailFromContainer(siblingLayout.querySelector('.acf-fc-layout-title'));
			if (siblingThumbnail) {
				return siblingThumbnail;
			}
		}

		return null;
	}

	function findPickerThumbnailNode(layout) {
		if (!layout) {
			return null;
		}

		const layoutType = getLayoutToneSeed(layout);
		if (!layoutType) {
			return null;
		}

		const pickerSelectors = [
			'.acfe-fc-layouts [data-layout]',
			'.acfe-fc-layouts a',
			'.acf-fc-popup [data-layout]',
			'.acf-fc-popup a',
			'.acf-tooltip [data-layout]',
			'.acf-tooltip a'
		];

		for (let index = 0; index < pickerSelectors.length; index += 1) {
			const candidates = document.querySelectorAll(pickerSelectors[index]);

			for (let candidateIndex = 0; candidateIndex < candidates.length; candidateIndex += 1) {
				const candidate = candidates[candidateIndex];
				const candidateSeed = normalizeIdentifier(
					candidate.getAttribute('data-layout') ||
					candidate.dataset.layout ||
					candidate.getAttribute('data-name') ||
					candidate.textContent
				);

				if (!candidateSeed || (candidateSeed !== layoutType && candidateSeed.indexOf(layoutType) === -1 && layoutType.indexOf(candidateSeed) === -1)) {
					continue;
				}

				const candidateThumbnail = cloneThumbnailFromContainer(candidate);
				if (candidateThumbnail) {
					return candidateThumbnail;
				}
			}
		}

		return null;
	}

	function findTemplateThumbnailNode(layout) {
		if (!layout) {
			return null;
		}

		const layoutType = getLayoutToneSeed(layout);
		if (!layoutType) {
			return null;
		}

		const templateLayouts = document.querySelectorAll('.layout.acf-clone, .acf-clone .layout, .acf-flexible-content .tmpl-layout');

		for (let index = 0; index < templateLayouts.length; index += 1) {
			const templateLayout = templateLayouts[index];
			const templateType = getLayoutToneSeed(templateLayout);

			if (!templateType || templateType !== layoutType) {
				continue;
			}

			const templateThumbnail = cloneThumbnailFromContainer(templateLayout.querySelector('.acf-fc-layout-title') || templateLayout);
			if (templateThumbnail) {
				return templateThumbnail;
			}
		}

		return null;
	}

	function ensureLayoutThumbnailNode(titleNode, layout) {
		const existingThumbnailNode = titleNode ? titleNode.querySelector('.layout-thumbnail') : null;
		if (!titleNode || !layout) {
			return;
		}

		if (existingThumbnailNode && hasUsableThumbnailNode(existingThumbnailNode)) {
			return;
		}

		if (existingThumbnailNode) {
			existingThumbnailNode.remove();
		}

		const thumbnailNode = findOriginalTitleThumbnailNode(titleNode) || findSiblingThumbnailNode(layout) || findTemplateThumbnailNode(layout) || findPickerThumbnailNode(layout);
		if (!thumbnailNode) {
			return;
		}

		const titleLabel = titleNode.querySelector('.layout-title');
		if (titleLabel) {
			titleLabel.insertAdjacentElement('beforebegin', thumbnailNode);
		} else {
			titleNode.insertAdjacentElement('afterbegin', thumbnailNode);
		}
	}

	function restoreLayoutParts(titleNode, layout) {
		if (!titleNode || !layout) {
			return;
		}

		preserveLayoutParts(titleNode, layout);

		const parts = getPreservedLayoutParts(layout);
		const titleLabel = titleNode.querySelector('.layout-title');

		if (!parts.thumbnail) {
			ensureLayoutThumbnailNode(titleNode, layout);
			preserveLayoutParts(titleNode, layout);
		}

		if ((!titleNode.querySelector('.layout-thumbnail') || !hasUsableThumbnailNode(titleNode.querySelector('.layout-thumbnail'))) && parts.thumbnail) {
			const thumbnailNode = parts.thumbnail.cloneNode(true);
			if (titleLabel) {
				titleLabel.insertAdjacentElement('beforebegin', thumbnailNode);
			} else {
				titleNode.insertAdjacentElement('afterbegin', thumbnailNode);
			}
		}

		if (!titleNode.querySelector('.pk-layout-card-meta') && parts.meta) {
			const metaNode = parts.meta.cloneNode(true);
			const activeTitleLabel = titleNode.querySelector('.layout-title');
			if (activeTitleLabel) {
				activeTitleLabel.insertAdjacentElement('afterend', metaNode);
			} else {
				titleNode.appendChild(metaNode);
			}
		}
	}

	function ensureLayoutTitleLabel(titleNode, layoutTitle) {
		if (!titleNode) {
			return null;
		}

		let titleLabel = titleNode.querySelector('.layout-title');
		const nextTitle = normalizeText(layoutTitle || getPlainLayoutTitleText(titleNode));

		if (!titleLabel) {
			titleLabel = createNode('span', 'layout-title', nextTitle);

			Array.prototype.slice.call(titleNode.childNodes).forEach(function (node) {
				if (node.nodeType === Node.TEXT_NODE && normalizeText(node.textContent)) {
					titleNode.removeChild(node);
				}
			});

			const metaNode = titleNode.querySelector('.pk-layout-card-meta');
			if (metaNode) {
				metaNode.insertAdjacentElement('beforebegin', titleLabel);
			} else {
				titleNode.appendChild(titleLabel);
			}
		} else if (nextTitle) {
			titleLabel.textContent = nextTitle;
		}

		return titleLabel;
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
		const tone = getToneIndex(getLayoutToneSeed(layout) || layoutTitle || header || String(index + 1));

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

		const titleLabel = ensureLayoutTitleLabel(titleNode, layoutData.layoutTitle);
		restoreLayoutParts(titleNode, layoutData.layout);

		let metaNode = titleNode.querySelector('.pk-layout-card-meta');
		if (!metaNode) {
			metaNode = createNode('div', 'pk-layout-card-meta');
			if (titleLabel) {
				titleLabel.insertAdjacentElement('afterend', metaNode);
			} else {
				titleNode.appendChild(metaNode);
			}
		}

		titleNode.classList.remove.apply(titleNode.classList, toneClassNames);
		titleNode.classList.add('pk-tone-' + layoutData.tone);
		layoutData.layout.classList.remove.apply(layoutData.layout.classList, toneClassNames);
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
