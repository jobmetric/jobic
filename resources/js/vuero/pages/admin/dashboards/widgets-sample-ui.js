import { initializeDashboard } from '../dashboard-runtime';
import { initializeWidgetInteractions } from '../dashboard/widgets';
import { initializeWidgetCharts } from '../dashboard/widget-charts';

initializeDashboard('widgets-sample-ui', ($page, actions) => {
    initializeWidgetInteractions($page, actions);
    initializeWidgetCharts($page);
});
