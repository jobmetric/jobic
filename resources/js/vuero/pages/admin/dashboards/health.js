import { initializeDashboard } from '../dashboard-runtime';
import { initializeLifestyle } from '../dashboard/lifestyle';

initializeDashboard('health', initializeLifestyle);
