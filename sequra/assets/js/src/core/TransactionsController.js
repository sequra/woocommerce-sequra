if (!window.SequraFE) {
    window.SequraFE = {};
}

(function () {
    /**
     * @typedef TransactionLog
     * @property {string} merchantReference
     * @property {number} executionId
     * @property {string} paymentMethod
     * @property {number} timestamp
     * @property {string} eventCode
     * @property {boolean} isSuccessful
     * @property {string} queueStatus
     * @property {string} reason
     * @property {string | null} failureDescription
     * @property {string} sequraLink
     * @property {string} shopLink
     */

    /**
     * @typedef TransactionData
     * @property {TransactionLog[]} transactionLogs
     * @property {boolean} hasNextPage
     */

    /**
     * Handles payment methods page logic.
     *
     * @param {{
     * getTransactionLogsUrl: string,
     * }} configuration
     * @constructor
     */
    function TransactionsController(configuration) {
        const {templateService, elementGenerator: generator, components, utilities} = SequraFE;
        /** @type AjaxServiceType */
        const api = SequraFE.ajaxService;
        /** @type string */
        let currentStoreId = '';
        /** @type Version */
        let version;
        /** @type Store[] */
        let stores;
        /** @type ConnectionSettings */
        let connectionSettings;
        /** @type TransactionLog[] */
        let transactionLogs;

        let hasNextPage = true;
        let isLoading = false;
        let isListenerSet = false;
        let page = 1;
        const limit = 10;

        /**
         * Displays page content.
         *
         * @param {{ state?: string, storeId: string }} config
         */
        this.display = ({storeId}) => {
            currentStoreId = storeId;
            templateService.clearMainPage();

            stores = SequraFE.state.getData('stores');
            version = SequraFE.state.getData('version');
            connectionSettings = SequraFE.state.getData('connectionSettings');
            transactionLogs = SequraFE.state.getData('transactionLogs');
            isListenerSet = transactionLogs !== null
            page = transactionLogs ? Math.ceil(transactionLogs.length / limit) : 1;

            if (transactionLogs) {
                initializePage();
                utilities.hideLoader();

                return;
            }

            Promise.all([
                transactionLogs ? [] : api.get(configuration.getTransactionLogsUrl + `&page=${page}&limit=${limit}`, null, SequraFE.customHeader),
            ]).then(([transactionLogsRes]) => {
                if (transactionLogsRes.length !== 0) {
                    transactionLogs = transactionLogsRes.transactionLogs;
                    hasNextPage = transactionLogsRes.hasNextPage;
                    SequraFE.state.setData('transactionLogs', transactionLogsRes.transactionLogs)
                }

                initializePage();
            }).finally(() => utilities.hideLoader());
        };

        /**
         * Renders the page contents.
         */
        const initializePage = () => {
            const pageWrapper = document.getElementById('sq-page-wrapper');

            pageWrapper.append(
                generator.createElement('div', 'sq-page-content-wrapper sqv--transactions', '', null, [
                    SequraFE.components.PageHeader.create(
                        {
                            currentVersion: version?.current,
                            newVersion: {
                                versionLabel: version?.new,
                                versionUrl: version?.downloadNewVersionUrl
                            },
                            mode: connectionSettings.environment === 'live' ? connectionSettings.environment : 'test',
                            activeStore: currentStoreId,
                            stores: stores.map((store) => ({label: store.storeName, value: store.storeId})),
                            onChange: (storeId) => {
                                if (storeId !== SequraFE.state.getStoreId()) {
                                    SequraFE.state.setStoreId(storeId);
                                    window.location.hash = '';
                                    SequraFE.state.display();
                                }
                            },
                            menuItems: SequraFE.utilities.getMenuItems(SequraFE.appStates.TRANSACTION)
                        }
                    ),
                    generator.createElement('div', 'sq-page-content', '', null, [
                        generator.createElement('div', 'sq-content-row', '', null, [
                            generator.createElement('main', 'sq-content', '', null, [
                                generator.createElement('div', 'sq-content-inner', '', null, [
                                    generator.createElement('div', 'sq-table-heading', '', null, [
                                        generator.createPageHeading({
                                            title: 'transaction.title',
                                            text: 'transaction.description'
                                        }),
                                    ]),
                                    transactionLogs?.length > 0 ?
                                        components.DataTable.create(getTableHeaders(), getTableRows(transactionLogs)) :
                                        utilities.createFlashMessage('transaction.noLogs', 'success')
                                ]),
                            ])
                        ])
                    ]),
                    generator.createSupportLink()
                ]));

            initializeInfiniteScroll();
        }

        /**
         * Returns table headers.
         *
         * @returns {TableCell[]}
         */
        const getTableHeaders = () => {
            return [
                {label: 'transaction.orderId', className: 'sqm--text-left'},
                {label: 'transaction.paymentMethod', className: 'sqm--text-left'},
                {label: 'transaction.dateTime', className: 'sqm--text-left'},
                {label: 'transaction.eventType', className: 'sqm--text-left'},
                {label: 'transaction.successful', className: 'sqm--text-left'},
                {label: 'transaction.status.title', className: 'sqm--text-left'},
                {label: 'transaction.details.title', className: 'sqm--text-left'}
            ];
        }

        /**
         * Returns table rows.
         *
         * @param {TransactionLog[]} logs
         *
         * @returns {TableCell[][]}
         */
        const getTableRows = (logs) => {
            return logs.map((transactionLog) => {
                return [
                    {label: transactionLog.merchantReference, className: 'sqm--text-left'},
                    {label: transactionLog.paymentMethod, className: 'sqm--text-left'},
                    {label: formatDate(transactionLog.timestamp), className: 'sqm--text-left'},
                    {label: transactionLog.eventCode.toUpperCase(), className: 'sqm--text-left'},
                    {
                        label: transactionLog.isSuccessful ? 'transaction.yes' : 'transaction.no',
                        className: 'sqm--text-left'
                    },
                    {
                        className: 'sqm--text-left',
                        renderer: (cell) =>
                            cell.append(
                                generator.createElement(
                                    'span',
                                    `sqp-status sqt--${transactionLog.queueStatus}`,
                                    `transaction.status.${transactionLog.queueStatus}`
                                )
                            )
                    },
                    transactionLog.failureDescription ? {
                        className: 'sqm--text-left',
                        renderer: (cell) =>
                            cell.append(
                                generator.createButton({
                                    type: 'text',
                                    label: 'transaction.details.label',
                                    onClick: () => renderDetails(transactionLog)
                                })
                            )
                    } : {},
                    {label: transactionLog.executionId.toString(), className: 'sq-row-id sqs--hidden'}
                ];
            });
        }

        /**
         * Renders the Transaction Log details.
         *
         * @param {TransactionLog} transactionLog
         */
        const renderDetails = (transactionLog) => {
            const existingDetails = document.getElementsByClassName('sqp-details')
            Array.from(existingDetails).map((e) => e.remove());

            const idCells = Array.from(document.getElementsByClassName('sq-row-id sqs--hidden'));
            const row = idCells.find((cell) => cell.textContent.trim() === transactionLog.executionId.toString()).parentElement;
            const detailsCell = generator.createElement('tr', 'sqp-details', '', null, [
                generator.createElement('td', '', '', {colSpan: 7}, [getDetails(transactionLog)])
            ])

            row.insertAdjacentElement('afterend', detailsCell);
        }

        /**
         * Returns the Transaction Log details element.
         *
         * @param {TransactionLog} transactionLog
         *
         * @return {HTMLElement}
         */
        const getDetails = (transactionLog) => {
            return generator.createElement('div', 'sqp-details-wrapper', '', null, [
                generator.createElement('div', 'sqp-details-row', '', null, [
                    generator.createElement('div', 'sqp-details-info', '', null, [
                        generator.createElement('span', 'sqp-details-label', 'transaction.details.reason'),
                        generator.createElement('span', 'sqp-details-message', transactionLog.reason),
                    ]),
                    generator.createButtonLink({
                        className: 'sq-link-button',
                        text: 'transaction.details.sequraLink',
                        href: transactionLog.sequraLink,
                        openInNewTab: true
                    })
                ]),
                generator.createElement('div', 'sqp-details-row', '', null, [
                    generator.createElement('div', 'sqp-details-info', '', null, [
                        generator.createElement('span', 'sqp-details-label', 'transaction.details.failureDescription'),
                        generator.createElement('span', 'sqp-details-message', transactionLog.failureDescription),
                    ]),
                    generator.createButtonLink({
                        className: 'sq-link-button',
                        text: 'transaction.details.shopLink',
                        href: transactionLog.shopLink,
                        openInNewTab: true
                    })
                ]),
            ]);
        }

        const initializeInfiniteScroll = () => {
            if(isListenerSet) {
                return;
            }

            window?.addEventListener('scroll', fetchNewPage)
        }

        const fetchNewPage = () => {
            const tableWrapper = document.querySelector(`.sqv--transactions .sq-table-container`);
            if (hasNextPage && !isLoading && hasScrolledToEnd()) {
                isLoading = true;
                let spinnerWrapper = generator.createElement('div', 'sq-loader sqt--large', '', '', [
                    generator.createElement('div', 'sqp-spinner')
                ]);

                tableWrapper?.append(spinnerWrapper);

                api.get(configuration.getTransactionLogsUrl + `&page=${page + 1}&limit=${limit}`, null, SequraFE.customHeader)
                    .then((res) => {
                        page++;
                        hasNextPage = res?.hasNextPage;
                        if(res?.transactionLogs?.length) {
                            components.DataTable.createTableRows(tableWrapper, getTableRows(res.transactionLogs));
                            transactionLogs = transactionLogs.concat(res.transactionLogs);
                            SequraFE.state.setData('transactionLogs', transactionLogs);
                        }
                    })
                    .finally(() => {
                        spinnerWrapper.remove();
                        isLoading = false;
                    });
            }
        }


        /**
         * Returns true if scrolled to end of page.
         */
        const hasScrolledToEnd = () => {
            const scrollTop = window.scrollY || window.pageYOffset;

            return scrollTop + window.innerHeight >= document.documentElement.scrollHeight;
        }

        /**
         * Formats the given date to the required table view.
         *
         * @param {number} timestamp
         *
         * @returns {string}
         */
        const formatDate = (timestamp) => {
            const dateTime = new Date(timestamp * 1000);
            const day = String(dateTime.getDate()).padStart(2, '0');
            const month = String(dateTime.getMonth() + 1).padStart(2, '0');
            const year = dateTime.getFullYear();

            const hours = String(dateTime.getHours()).padStart(2, '0');
            const minutes = String(dateTime.getMinutes()).padStart(2, '0');

            return `${day}-${month}-${year} ${hours}:${minutes}`;
        }
    }

    SequraFE.TransactionsController = TransactionsController;
})();
