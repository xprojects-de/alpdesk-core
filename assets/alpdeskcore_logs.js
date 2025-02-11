import {Application} from '@hotwired/stimulus';
import LogsController from './controllers/logs-controller';

import './styles/alpdeskcore_logs.css';

const alpdeskApplication = Application.start();
alpdeskApplication.register('alpdeskcorelogs', LogsController);
