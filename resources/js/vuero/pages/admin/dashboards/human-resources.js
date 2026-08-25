import { initializeDashboard } from '../dashboard-runtime';
import { initializeBusinessDashboard } from '../dashboard/business';

initializeDashboard('human-resources', initializeBusinessDashboard('human-resources'));
