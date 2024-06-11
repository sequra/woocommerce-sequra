/**
* @typedef RepeaterAdapter
* @property {string} containerSelector
* @property {array} data
* @property {function} getHeaders
* @property {function} getRow
* @property {string} addRowText
* @property {string} removeSelectedRowsText
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

        this.checkAllCheckbox.addEventListener('change', this.#changeAllChecks);
        this.elem.querySelector('.sq-add').addEventListener('click', this.#addRow);
        this.elem.querySelector('.sq-remove').addEventListener('click', this.#deleteRows);

        this.adapter.data.forEach((row) => this.#addRow(null, row));

        this.#checkboxes().forEach((checkbox) => {
            if (readonly) {
                checkbox.disabled = true;
                return;
            }
            checkbox.addEventListener('change', this.#uncheckCheckAll)
        });
    }

    #checkboxes = () => this.elem.querySelectorAll('.sq-table__body >.sq-table__row input[type="checkbox"]:first-child');

    #changeAllChecks = (e) => {
        if ('undefined' !== typeof e?.preventDefault) {
            e?.preventDefault();
        }
        this.#checkboxes().forEach((checkbox) => {
            checkbox.checked = e.target.checked

            if (e.target.checked) {
                checkbox.closest('.sq-table__row').classList.add('sq-table__row--selected');
            } else {
                checkbox.closest('.sq-table__row').classList.remove('sq-table__row--selected');
            }
        });
    };

    #uncheckCheckAll = (e) => {
        if ('undefined' !== typeof e?.preventDefault) {
            e?.preventDefault();
        }
        // get parent with class .sq-table__row
        const ch = this.#checkboxes();
        if (!ch) return;
        // check if all checkboxes are checked
        let allChecked = true;
        for (const checkbox of ch) {
            if (!checkbox.checked) {
                if (allChecked) {
                    allChecked = false;
                }
                checkbox.closest('.sq-table__row').classList.remove('sq-table__row--selected');
            } else {
                checkbox.closest('.sq-table__row').classList.add('sq-table__row--selected');
            }
        }
        this.checkAllCheckbox.checked = allChecked;
    };

    #addRow = (e, data = null) => {
        if ('undefined' !== typeof e?.preventDefault) {
            e?.preventDefault();
        }
        // clone template
        const clone = this.template.cloneNode(true);
        const row = clone.content.querySelector('.sq-table__row');
        row.querySelector('.sq-table__row-content').innerHTML = this.adapter.getRow(data);
        // set checkbox change event
        row.querySelector('input[type="checkbox"]:first-child').addEventListener('change', this.#uncheckCheckAll);
        this.checkAllCheckbox.checked = false;

        // append to .sq-table__body
        this.body.appendChild(row);

        this.#updateVisibility();
    };

    #deleteRows = (e) => {
        if ('undefined' !== typeof e?.preventDefault) {
            e?.preventDefault();
        }
        this.#checkboxes().forEach((checkbox) => {
            if (checkbox.checked) {
                checkbox.closest('.sq-table__row').remove();
            }
        });
        this.checkAllCheckbox.checked = false;

        this.#updateVisibility();
    }

    #updateVisibility = () => {
        const rows = this.elem.querySelectorAll('.sq-table__body >.sq-table__row');
        if (rows.length === 0) {
            // this.elem.querySelector('.sq-table__header').classList.add('sqs--hidden');
            this.elem.querySelector('.sq-table__header input[type="checkbox"]').disabled = true;
            this.elem.querySelector('.sq-table__body').classList.add('sqs--hidden');
            this.elem.querySelector('.sq-table__footer .sq-remove').classList.add('sqs--hidden');
        } else{
            // this.elem.querySelector('.sq-table__header').classList.remove('sqs--hidden');
            this.elem.querySelector('.sq-table__header input[type="checkbox"]').disabled = false;
            this.elem.querySelector('.sq-table__body').classList.remove('sqs--hidden');
            this.elem.querySelector('.sq-table__footer .sq-remove').classList.remove('sqs--hidden');
        }
    }

    #renderTable = () => {
        const repeater = document.createElement('div');
        repeater.classList.add('sq-table');
        repeater.innerHTML = `
            <header class="sq-table__header">
                <div class="sq-checkbox"><input type="checkbox" class="sq-check-all" disabled><span class="sqp-checkmark"></span></div>
                ${this.adapter.getHeaders().map((header) => `<h3 class="sqp-field-title">${header}</h3>`).join('')}
			</header>
			<div class="sq-table__body sqs--hidden">
				<template>
					<div class="sq-table__row">
                        <div class="sq-checkbox"><input type="checkbox"><span class="sqp-checkmark"></span></div>
						<div class="sq-table__row-content"></div>	
					</div>
				</template>
			</div>
			<footer class="sq-table__footer">
				<button class="sq-button sq-add sqm--small" type="button">${SequraFE.translationService.translate(this.adapter.addRowText)}</button>
				<button class="sq-button sq-remove sqt--danger sqm--small sqs--hidden" type="button">${SequraFE.translationService.translate(this.adapter.removeSelectedRowsText)}</button>
			</footer>`.trim();

        this.elem.appendChild(repeater);
    }
}