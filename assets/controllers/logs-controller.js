import {Controller} from "@hotwired/stimulus";

export default class LogsController extends Controller {

    show() {

        const children = this.element.children;
        if (children !== null && children !== undefined && children.length > 2) {

            const container = children[2];

            const requestToken = this.element.getAttribute('data-token');
            const fileName = this.element.getAttribute('data-filename');
            const filterValue = this.element.getAttribute('data-filtervalue');

            if (container.style.display === 'none' || container.style.display === '') {

                const xhr = new XMLHttpRequest();

                xhr.open('POST', '/contao/alpdeskcorelazylogs', true);
                xhr.setRequestHeader('Content-Type', 'application/json');
                xhr.setRequestHeader('contaoCsrfToken', requestToken);

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

                        container.innerHTML = containerInnerHtml;

                    } else {
                        container.innerHTML = '<div class="alpdeskcore-errorContainer">Error loading data</div>';
                    }


                };

                const jsonPayload = {
                    'logFileName': fileName,
                    'filterValue': filterValue
                };

                container.innerHTML = '<div class="alpdeskcore-loader"></div>';
                container.style.display = 'block';

                xhr.send(JSON.stringify(jsonPayload));

            } else {
                container.style.display = 'none';
            }

        }

    }

}