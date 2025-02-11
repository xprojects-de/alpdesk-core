import {Application} from '@hotwired/stimulus';
import LogsController from "./controllers/logs-controller"

const alpdeskApplication = Application.start();
alpdeskApplication.register("AlpdeskLogsController", LogsController);
