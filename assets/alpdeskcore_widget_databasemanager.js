import {Application} from '@hotwired/stimulus';
import DatabaseController from "./controllers/database-controller"

const alpdeskApplication = Application.start();
alpdeskApplication.register("AlpdeskDatabaseController", DatabaseController);

