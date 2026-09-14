<?php

return [
    'completed' => ! in_array(env('INSTALLATION'), [null, '', false, 0, '0'], true),
    'status' => env('INSTALLATION_STATUS'),
    'step' => (int) env('INSTALLATION_STEP', 0),
    'task' => (int) env('INSTALLATION_TASK', 0),
];
