(function (window, document) {

    function ready(callback) {
        if (document.readyState !== 'loading') {
            callback();
        } else if (document.addEventListener) {
            document.addEventListener('DOMContentLoaded', callback);
        }
    }

    ready(function () {

        const logsContainer = document.getElementById('alpdeskcorelogs_backendcontainer');
        if (logsContainer !== null && logsContainer !== undefined) {

            const logFiles = logsContainer.querySelectorAll('div.alpdeskcorelogfile');
            for (const logFile of logFiles) {

                const children = logFile.children;
                if (children !== null && children !== undefined && children.length > 2) {

                    const visible = children[1];
                    const container = children[2];

                    const requestToken = logFile.getAttribute('data-token');
                    const fileName = logFile.getAttribute('data-filename');

                    visible.addEventListener('click', function () {

                        if (container.style.display === 'none' || container.style.display === '') {

                            const xhr = new XMLHttpRequest();

                            xhr.open('POST', '/contao/alpdeskcorelazylogs', true);
                            xhr.setRequestHeader('Content-Type', 'application/json');
                            xhr.setRequestHeader('contaoCsrfToken', requestToken);

                            xhr.onload = function () {

                                if (xhr.status === 200) {

                                    const data = JSON.parse(xhr.responseText);
                                    console.log(data);

                                }

                            };

                            const jsonPayload = {
                                'logFileName': fileName
                            };

                            xhr.send(JSON.stringify(jsonPayload));

                            container.style.display = 'block';

                        } else {
                            container.style.display = 'none';
                        }


                    });

                }

            }

        }


    }, false);
})(window, document);

