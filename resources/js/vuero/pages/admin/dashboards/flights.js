import { initializeDashboard } from '../dashboard-runtime';
import { initializeBusinessDashboard } from '../dashboard/business';

initializeDashboard('flights', initializeBusinessDashboard('flights'));
