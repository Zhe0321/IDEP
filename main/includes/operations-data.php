<?php
declare(strict_types=1);

$measurementRecords = [
    ['date' => '14 Jul', 'wellId' => 'RW-01', 'village' => 'Ubud', 'waterLevel' => '2.31 m', 'quality' => 'Good', 'export' => 'CSV'],
    ['date' => '14 Jul', 'wellId' => 'RW-07', 'village' => 'Tabanan', 'waterLevel' => '1.82 m', 'quality' => 'Good', 'export' => 'Excel'],
    ['date' => '14 Jul', 'wellId' => 'RW-12', 'village' => 'Denpasar', 'waterLevel' => '3.05 m', 'quality' => 'Review', 'export' => 'CSV'],
    ['date' => '13 Jul', 'wellId' => 'RW-18', 'village' => 'Gianyar', 'waterLevel' => '2.10 m', 'quality' => 'Good', 'export' => 'Excel'],
    ['date' => '13 Jul', 'wellId' => 'RW-23', 'village' => 'Badung', 'waterLevel' => '1.67 m', 'quality' => 'Good', 'export' => 'CSV'],
];

$alerts = [
    [
        'title' => 'Inactive',
        'wellId' => 'RW-12',
        'village' => 'Denpasar',
        'summary' => 'Inactive',
        'severity' => 'critical',
        'state' => 'Open',
        'alert' => 'No Activity',
        'signal' => 'Weak',
        'lastTransmission' => '35 min ago',
        'action' => 'Field check',
    ],
    [
        'title' => 'Weak Signal',
        'wellId' => 'RW-31',
        'village' => 'Jembrana',
        'summary' => 'Signal below threshold',
        'severity' => 'warning',
        'state' => 'Open',
        'alert' => 'Low Signal',
        'signal' => 'Weak',
        'lastTransmission' => '20 min ago',
        'action' => 'Inspect antenna',
    ],
    [
        'title' => 'Missing Transmission',
        'wellId' => 'RW-04',
        'village' => 'Sanur',
        'summary' => 'No data for 4 hours',
        'severity' => 'critical',
        'state' => 'Open',
        'alert' => 'Missing Data',
        'signal' => 'No Signal',
        'lastTransmission' => '4 hours ago',
        'action' => 'Check sensor power',
    ],
    [
        'title' => 'Data Quality Review',
        'wellId' => 'RW-07',
        'village' => 'Tabanan',
        'summary' => 'Unexpected level jump',
        'severity' => 'warning',
        'state' => 'Open',
        'alert' => 'Quality Review',
        'signal' => 'Strong',
        'lastTransmission' => '15 min ago',
        'action' => 'Validate reading',
    ],
    [
        'title' => 'Normalised',
        'wellId' => 'RW-18',
        'village' => 'Gianyar',
        'summary' => 'Back within threshold',
        'severity' => 'resolved',
        'state' => 'Resolved',
        'alert' => 'Recovered',
        'signal' => 'Strong',
        'lastTransmission' => '10 min ago',
        'action' => 'No action required',
    ],
];

$generatedReports = [
    ['name' => 'Ubud Groundwater Summary', 'period' => 'Mar–May 2026', 'createdBy' => 'Field Team', 'date' => '14 Jul', 'status' => 'Ready', 'export' => 'PDF'],
    ['name' => 'Sensor Status Overview', 'period' => 'Last 7 Days', 'createdBy' => 'Research Admin', 'date' => '13 Jul', 'status' => 'Ready', 'export' => 'Excel'],
    ['name' => 'Recharge Well Review', 'period' => 'Q2 2026', 'createdBy' => 'IDEP Team', 'date' => '10 Jul', 'status' => 'Draft', 'export' => 'CSV'],
];

$hardwareRecords = [
    [
        'name' => 'Well_id1',
        'city' => 'Badung',
        'mac' => '00:1B:44:11:3A:29',
        'date' => '2026-07-20',
        'displayDate' => '20/07/2026',
        'installer' => 'PT. Banyu Biru',
        'type' => 'Type 4',
        'longitude' => '115.1786',
        'latitude' => '-8.5819',
    ],
    [
        'name' => 'Well_id2',
        'city' => 'Bangli',
        'mac' => '00:3T:45:13:3A:92',
        'date' => '2026-07-05',
        'displayDate' => '05/07/2026',
        'installer' => 'PT. Banyu Biru',
        'type' => 'Type 3',
        'longitude' => '115.3542',
        'latitude' => '-8.4543',
    ],
];
