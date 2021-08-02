(function (window, document) {

    function ready(callback) {
        if (document.readyState !== 'loading') {
            callback();
        } else if (document.addEventListener) {
            document.addEventListener('DOMContentLoaded', callback);
        } else {
            document.attachEvent('onreadystatechange', function () {
                if (document.readyState === 'complete') {
                    callback();
                }
            });
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

                    visible.addEventListener('click', function (event) {

                        if (container.style.display === 'none' || container.style.display === '') {
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

