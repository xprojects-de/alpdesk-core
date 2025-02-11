import {Controller} from "@hotwired/stimulus";

export default class DatabaseController extends Controller {

    static values = {
        id: String,
        do: String,
        act: String,
        rt: String
    }

    migrate() {
        const migrationButton = this.element;

        const buttonLabel = migrationButton.innerHTML;

        migrationButton.innerHTML = buttonLabel + ' <span style="color:red;">( loading... )</span>';

        const urlParams = {
            do: this.doValue,
            id: this.idValue,
            act: this.actValue,
            rt: this.rtValue
        };

        const urlParamsMigrate = {...urlParams, alpdeskcore_dbmigration: 1};

        const urlReload = new URLSearchParams(urlParams);
        const urlMigrate = new URLSearchParams(urlParamsMigrate);

        fetch('/contao?' + urlMigrate.toString(), {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        }).then(() => {
            window.location.href = '/contao?' + urlReload.toString();
        }).catch(() => {
            window.location.href = '/contao?' + urlReload.toString();
        });

    }

}