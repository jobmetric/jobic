import { initializeDashboard } from '../dashboard-runtime';
import { initializeBusinessDashboard } from '../dashboard/business';

initializeDashboard('course', initializeBusinessDashboard('course'));
