import { startStimulusApp } from '@symfony/stimulus-bundle';
import CalendarController from './controllers/calendar_controller.js';
import LogoutController from './controllers/logout_controller.js';
import NotificationController from './controllers/notification_controller.js';
import SearchController from './controllers/search_controller.js';
import TimeslotController from './controllers/timeslot_controller.js';

const app = startStimulusApp();

app.register('calendar', CalendarController);
app.register('logout', LogoutController);
app.register('notification', NotificationController);
app.register('search', SearchController);
app.register('timeslot', TimeslotController);
