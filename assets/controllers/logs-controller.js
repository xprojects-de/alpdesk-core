import {Controller} from "@hotwired/stimulus";

export default class LogsController extends Controller {

    static targets = ['logoutput'];

    static values = {
        token: String,
        filename: String,
        filter: String,
        confirm: String,
        deleteurl: String
    }

    show() {

        const outputTarget = this.logoutputTarget;

        if (outputTarget.style.display === 'none' || outputTarget.style.display === '') {

            outputTarget.innerHTML = '<div class="alpdeskcore-loader"></div>';
            outputTarget.style.display = 'block';

            fetch('/contao/alpdeskcorelazylogs', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'contaoCsrfToken': this.tokenValue
                },
                body: JSON.stringify({
                    REQUEST_TOKEN: this.tokenValue,
                    logFileName: this.filenameValue,
                    filterValue: this.filterValue
                })
            }).then((xhr) => {

                if (xhr.status === 200) {

                    xhr.json().then((data) => {

                        let containerInnerHtml = '<div class="alpdeskcore-errorContainer">Error loading data</div>';

                        if (
                            data.error !== undefined && data.error !== null &&
                            data.content !== undefined && data.content !== null &&
                            data.error === false
                        ) {

                            containerInnerHtml = '';
                            data.content.forEach((val) => {
                                containerInnerHtml += '<div class="alpdeskcorelogitem"><p>' + val + '</p></div>';
                            });

                        }

                        outputTarget.innerHTML = containerInnerHtml;

                    }).catch(() => {
                        outputTarget.innerHTML = '<div class="alpdeskcore-errorContainer">Error loading data</div>';
                    });

                } else {
                    outputTarget.innerHTML = '<div class="alpdeskcore-errorContainer">Error loading data</div>';
                }

            }).catch(() => {
                outputTarget.innerHTML = '<div class="alpdeskcore-errorContainer">Error loading data</div>';
            });

        } else {
            outputTarget.style.display = 'none';
        }

    }

    delete() {

        if (confirm(this.confirmValue)) {
            window.location.href = this.deleteurlValue;
        }

    }

}