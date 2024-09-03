/**
* @typedef RepeaterAdapter
* @property {string} containerSelector
* @property {array} data
* @property {function} getHeaders
* @property {function} getRowContent
* @property {function} getRowHeader
* @property {function|null} handleChange
* @property {string} addRowText
*/

/**
 * Repeater component.
 */
export class Repeater {

    /**
     * 
     * @param {RepeaterAdapter} adapter 
     */
    constructor(adapter) {
        this.adapter = adapter;
        this.elem = document.querySelector(this.adapter.containerSelector);
        if (!this.elem) return;

        this.#renderTable();

        this.checkAllCheckbox = this.elem.querySelector('.sq-check-all');

        this.body = this.elem.querySelector('.sq-table__body');
        this.template = this.body.querySelector('template');

        const addBtn = this.elem.querySelector('.sq-add');
        if (addBtn) {
            addBtn.addEventListener('click', this.#addRow);
        }

        this.adapter.data.forEach((row) => this.#addRow(null, row));
    }

    #addRow = (e, data = null) => {
        if ('undefined' !== typeof e?.preventDefault) {
            e?.preventDefault();
        }
        const clone = this.template.cloneNode(true);
        const row = clone.content.querySelector('.sq-table__row');
        row.querySelector('.sq-table__row-header').innerHTML = this.adapter.getRowHeader(data);
        row.querySelector('.sq-table__row-content').innerHTML = this.adapter.getRowContent(data);


        const rmBtn = row.querySelector('.sq-remove');
        if (rmBtn) {
            rmBtn.addEventListener('click', this.#deleteRow);
        }

        this.body.appendChild(row);

        // row.querySelector('.sq-table__row-content').querySelectorAll('input,textarea,select').forEach(elem => {
        row.querySelectorAll('input,textarea,select').forEach(elem => {
            elem.addEventListener('change', () => {
                if (this.adapter.handleChange) {
                    this.adapter.handleChange(this.elem.querySelector('.sq-table'))
                }
            });
        });

        this.#updateVisibility();
    };

    #deleteRow = e => {
        if ('undefined' !== typeof e?.preventDefault) {
            e?.preventDefault();
        }
        e.target.closest('.sq-table__row').remove();
        this.#updateVisibility();
    }

    #updateVisibility = () => {
        const rows = this.elem.querySelectorAll('.sq-table__body >.sq-table__row');
        if (rows.length === 0) {
            this.elem.querySelector('.sq-table__body').classList.add('sqs--hidden');
        } else {
            this.elem.querySelector('.sq-table__body').classList.remove('sqs--hidden');
        }

        if (this.adapter.handleChange) {
            this.adapter.handleChange(this.elem.querySelector('.sq-table'));
        }
    }

    #renderTable = () => {
        const repeater = document.createElement('div');
        repeater.classList.add('sq-table');

        let canAdd = true;
        if ('undefined' !== typeof this.adapter.canAdd) {
            canAdd = this.adapter.canAdd;
        }

        let canRemove = true;
        if ('undefined' !== typeof this.adapter.canRemove) {
            canRemove = this.adapter.canRemove;
        }

        let nameAttr = '';
        if ('undefined' !== typeof this.adapter.name) {
            nameAttr = `name="${this.adapter.name}"`;
        }

        const addBtn = !canAdd ? '' : `<button class="sq-button sq-add sqm--small" type="button">${SequraFE.translationService.translate(this.adapter.addRowText)}</button>`;
        const rmBtn = !canRemove ? '' : `<button class="sq-button sq-remove sqm--small" type="button">${SequraFE.translationService.translate(this.adapter.removeRowText)}</button>`;
        repeater.innerHTML = `
            <header class="sq-table__header">
                ${this.adapter.getHeaders().map(header => `<div class="sq-table__header-item"><h3 class="sqp-field-title">${header.title}</h3><span class="sqp-field-subtitle">${header.description}</span></div>`).join('')}
                ${addBtn}
			</header>
			<div class="sq-table__body sqs--hidden">
				<template>
					<details class="sq-table__row" ${nameAttr}>
                        <summary class="sq-table__row-header"></summary>
						<div class="sq-table__row-content"></div>	
                        ${rmBtn}
					</details>
				</template>
			</div>
			`.trim();

        this.elem.appendChild(repeater);
    }
}