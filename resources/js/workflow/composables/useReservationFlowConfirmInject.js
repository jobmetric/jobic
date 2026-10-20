import { inject } from 'vue';

import { RESERVATION_FLOW_CONFIRM_KEY } from './reservationFlowConfirmKey.js';

export function useReservationFlowConfirmInject({ optional = false } = {}) {
    const confirm = inject(RESERVATION_FLOW_CONFIRM_KEY, null);

    if (!confirm) {
        if (optional) {
            return async () => false;
        }

        throw new Error('Reservation flow confirm is not available in this component tree.');
    }

    return confirm;
}
