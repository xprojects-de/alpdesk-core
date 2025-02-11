import {Controller} from "@hotwired/stimulus";

export default class DatabaseController extends Controller {

    migrate() {
        const migrationButton = this.element;

        const buttonLabel = migrationButton.innerHTML;

        const idParam = migrationButton.getAttribute('data-id');
        const doParam = migrationButton.getAttribute('data-do');
        const actParam = migrationButton.getAttribute('data-act');
        const rtParam = migrationButton.getAttribute('data-rt');

        migrationButton.innerHTML = buttonLabel + ' <span style="color:red;">( loading... )</span>';

        new Request.Contao({
            'url': '/contao',
            followRedirects: false,
            onSuccess: function () {
                window.location.reload();
            },
            onError: function () {
                window.location.reload();
            },
            onFailure: function () {
                window.location.reload();
            }
        }).get({'do': doParam, 'id': idParam, 'act': actParam, 'rt': rtParam, 'alpdeskcore_dbmigration': 1});

    }

}