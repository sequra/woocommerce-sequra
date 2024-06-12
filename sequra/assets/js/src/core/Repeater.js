/**
* @typedef RepeaterAdapter
* @property {string} containerSelector
* @property {array} data
* @property {function} getHeaders
* @property {function} getRow
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

        this.elem.querySelector('.sq-add').addEventListener('click', this.#addRow);

        this.adapter.data.forEach((row) => this.#addRow(null, row));
    }

    #addRow = (e, data = null) => {
        if ('undefined' !== typeof e?.preventDefault) {
            e?.preventDefault();
        }
        const clone = this.template.cloneNode(true);
        const row = clone.content.querySelector('.sq-table__row');
        row.querySelector('.sq-table__row-content').innerHTML = this.adapter.getRow(data);
        row.querySelector('.sq-remove').addEventListener('click', this.#deleteRow);
        this.body.appendChild(row);

        row.querySelector('.sq-table__row-content').querySelectorAll('input,textarea,select').forEach(elem => {
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
        repeater.innerHTML = `
            <header class="sq-table__header">
                ${this.adapter.getHeaders().map(header => `<div class="sq-table__header-item"><h3 class="sqp-field-title">${header.title}</h3><span class="sqp-field-subtitle">${header.description}</span></div>`).join('')}
			</header>
			<div class="sq-table__body sqs--hidden">
				<template>
					<div class="sq-table__row">
						<div class="sq-table__row-content"></div>	
                        <button class="sq-button sq-remove sqm--small" type="button"></button>
					</div>
				</template>
			</div>
			<footer class="sq-table__footer">
				<button class="sq-button sq-add sqm--small" type="button">${SequraFE.translationService.translate(this.adapter.addRowText)}</button>
			</footer>`.trim();

        this.elem.appendChild(repeater);
    }
}