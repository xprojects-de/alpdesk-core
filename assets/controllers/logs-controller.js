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

            const xhr = new XMLHttpRequest();

            xhr.open('POST', '/contao/alpdeskcorelazylogs', true);
            xhr.setRequestHeader('Content-Type', 'application/json');
            xhr.setRequestHeader('contaoCsrfToken', this.tokenValue);

            xhr.onload = function () {

                if (xhr.status === 200) {

                    let containerInnerHtml = '<div class="alpdeskcore-errorContainer">Error loading data</div>';

                    const data = JSON.parse(xhr.responseText);
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

                } else {
                    outputTarget.innerHTML = '<div class="alpdeskcore-errorContainer">Error loading data</div>';
                }

            };

            const jsonPayload = {
                'logFileName': this.filenameValue,
                'filterValue': this.filterValue
            };

            outputTarget.innerHTML = '<div class="alpdeskcore-loader"></div>';
            outputTarget.style.display = 'block';

            xhr.send(JSON.stringify(jsonPayload));

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