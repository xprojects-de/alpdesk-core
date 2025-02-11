import {Application} from '@hotwired/stimulus';
import DatabaseController from './controllers/database-controller';

import './styles/alpdeskcore_widget_databasemanager.css';

const alpdeskApplication = Application.start();
alpdeskApplication.register('alpdeskcoredatabase', DatabaseController);

