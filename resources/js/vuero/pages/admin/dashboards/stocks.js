import { initializeDashboard } from '../dashboard-runtime';
import { initializeStocks } from '../dashboard/stocks';

initializeDashboard('stocks', initializeStocks);
