import { initializeDashboard } from '../dashboard-runtime';
import { initializeMessagingV2 } from '../dashboard/messaging-app';

initializeDashboard('messaging-v2', initializeMessagingV2);
