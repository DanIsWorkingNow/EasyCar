<?php

return [
    // How long after a booking is completed the customer may review it.
    'window_days' => (int) env('REVIEWS_WINDOW_DAYS', 90),
];
