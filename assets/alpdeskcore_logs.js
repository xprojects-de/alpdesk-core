import {Application} from '@hotwired/stimulus';
import LogsController from "./controllers/logs-controller"

const application = Application.start();
application.register("AlpdeskLogsController", LogsController);
