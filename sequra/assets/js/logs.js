(function () {
    Logs = {
        init: function () {
            this.content = document.getElementById('sequra-logs-content');
            this.contentTitle = document.querySelector('.sequra-logs__title');
            this.contentError = document.querySelector('.sequra-logs__error');
            this.contentSuccess = document.querySelector('.sequra-logs__success');
            this.reload = document.getElementById('sequra-logs-reload');
            this.clear = document.getElementById('sequra-logs-clear');
            this.dialog = document.querySelector('.sequra-modal');

            this.bindEvents();
            this.handleLoad();
        },
        bindEvents: function () {
            this.reload.addEventListener('click', this.handleLoad.bind(this));
            this.clear.addEventListener('click', this.handleClearConfirmation.bind(this));

            this.dialog.querySelector('.sequra-modal__ko').addEventListener('click', () => this.dialog.close());
            this.dialog.querySelector('.sequra-modal__ok').addEventListener('click', this.handleClear.bind(this));
        },

        updateDom: function (args) {
            const isLoading = args?.isLoading || false;

            this.contentError.textContent = args?.error || '';
            this.contentSuccess.textContent = args?.success || '';

            if (isLoading) {
                this.contentTitle.textContent = 'Loading...';
                this.content.classList.remove('active');
                this.content.value = '';
                return
            }

            this.contentTitle.textContent = 'Logs';

            if ('undefined' !== typeof args.content) {
                this.content.value = args.content;
            }

            if (this.content.value) {
                this.content.classList.add('active');
            } else {
                this.content.classList.remove('active');
            }

        },

        ajax: function (args) {
            return fetch(sequraLogs.url, {
                mode: 'same-origin',
                method: args?.method || 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': sequraLogs.nonce
                },
            })
        },

        handleLoad: function () {
            this.updateDom({ isLoading: true });
            this.ajax()
                .then(response => {
                    if (!response.ok) {
                        // decode the response body
                        return response.json().then(text => {
                            throw new Error(text?.message || response.statusText);
                        });
                    }
                    return response.text()
                })
                .then(data => {
                    this.updateDom({ success: '', content: data })
                })
                .catch(e => {
                    console.error(e);
                    this.updateDom({ error: e.message || 'Error loading logs' })
                })
        },

        handleClearConfirmation: function () {
            this.dialog.showModal();
        },
        handleClear: function () {
            this.dialog.close()
            this.updateDom({ isLoading: true });

            this.ajax({ method: 'DELETE' })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(response.statusText);
                    }
                    return response.json()
                })
                .then(data => {
                    this.updateDom({ success: data.message || 'Logs deleted', content: '' })
                })
                .catch(e => {
                    console.error(e);
                    this.updateDom({ error: e.message || 'Error clearing logs' })
                })
        },
    };

    Logs.init();
})();