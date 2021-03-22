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

        const migrationButton = document.getElementById('alpdeskcore_dbmigration_button');

        if (migrationButton !== undefined && migrationButton !== null) {

            const buttonLabel = migrationButton.innerHTML;
            migrationButton.onclick = function (event) {

                event.preventDefault();

                const idParam = migrationButton.getAttribute('data-id');
                const doParam = migrationButton.getAttribute('data-do');
                const actParam = migrationButton.getAttribute('data-act');
                const rtParam = migrationButton.getAttribute('data-rt');

                migrationButton.innerHTML = buttonLabel + ' <span style="color:red;">( loading... )</span>';

                new Request.Contao({
                    'url': '/contao',
                    followRedirects: false,
                    onSuccess: function (responseJSON, responseText) {
                        window.location.reload();
                    },
                    onError: function (text, error) {
                        window.location.reload();
                    },
                    onFailure: function (f) {
                        window.location.reload();
                    }
                }).get({'do': doParam, 'id': idParam, 'act': actParam, 'rt': rtParam, 'alpdeskcore_dbmigration': 1});

            };

        }

    }, false);
})(window, document);

