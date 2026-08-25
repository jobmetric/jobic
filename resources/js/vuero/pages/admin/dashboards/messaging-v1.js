import { initializeDashboard } from '../dashboard-runtime';
import { initializeMessagingV1 } from '../dashboard/messaging-app';

initializeDashboard('messaging-v1', initializeMessagingV1);
