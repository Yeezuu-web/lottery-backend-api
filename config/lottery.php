<?php

declare(strict_types=1);

return [
    'channels' => [
        'A',
        'B',
        'C',
        'D',
        'LO',
        'HO',
        'N',
        'I',
    ],

    'type' => [
        '2D',
        '3D',
    ],

    'periods' => [
        'evening', // 4:30 PM
        'night', // 6:30 PM
    ],

    'options' => [
        'x',
        '\\',
        '>|',
        '\\|',
        '>',
        'none',
    ],

    'provinces' => [
        'TP.HCM',
        'Hà Nội',
        'Đồng Tháp',
        'Bến Tre',
        'Vũng Tàu',
        'Bạc Liêu',
        'Đồng Nai',
        'Sóc Trăng',
        'Tây Ninh',
        'Bình Thuận',
        'An Giang',
        'Vĩnh Long',
        'Long An',
        'Tiền Giang',
        'Kiên Giang',
        'Cần Thơ',
        'Miền Bắc',
    ],

    'default_province' => [
        'monday' => [
            'evening' => [
                '2D' => [
                    'A' => 'TP.HCM',
                    'B' => 'TP.HCM',
                    'C' => 'TP.HCM',
                    'D' => 'TP.HCM',
                    'LO' => 'TP.HCM',
                    'N' => 'Đồng Tháp',
                    'HO' => 'Đồng Tháp',
                    'I' => 'Đồng Tháp',
                ],
                '3D' => [
                    'A' => 'TP.HCM',
                    'B' => 'TP.HCM',
                    'C' => 'TP.HCM',
                    'D' => 'TP.HCM',
                    'LO' => 'TP.HCM',
                    'N' => 'Đồng Tháp',
                    'HO' => 'Đồng Tháp',
                    'I' => 'Đồng Tháp',
                ],
            ],
            'night' => [
                '2D' => [
                    'A' => 'Miền Bắc',
                    'B' => 'Miền Bắc',
                    'C' => 'Miền Bắc',
                    'D' => 'Miền Bắc',
                    'LO' => 'Miền Bắc',
                ],
                '3D' => [
                    'A' => 'Miền Bắc',
                    'B' => 'Miền Bắc',
                    'C' => 'Miền Bắc',
                    'D' => 'Miền Bắc',
                    'LO' => 'Miền Bắc',
                ],
            ],
        ],
        'tuesday' => [
            'evening' => [
                '2D' => [
                    'A' => 'Bến Tre',
                    'B' => 'Bến Tre',
                    'C' => 'Bến Tre',
                    'D' => 'Bến Tre',
                    'LO' => 'Bến Tre',
                    'N' => 'Vũng Tàu',
                    'HO' => 'Vũng Tàu',
                    'I' => 'Bạc Liêu',
                ],
                '3D' => [
                    'A' => 'Bến Tre',
                    'B' => 'Bến Tre',
                    'C' => 'Bến Tre',
                    'D' => 'Bến Tre',
                    'LO' => 'Bến Tre',
                    'N' => 'Vũng Tàu',
                    'HO' => 'Vũng Tàu',
                    'I' => 'Bạc Liêu',
                ],
            ],
            'night' => [
                '2D' => [
                    'A' => 'Miền Bắc',
                    'B' => 'Miền Bắc',
                    'C' => 'Miền Bắc',
                    'D' => 'Miền Bắc',
                    'LO' => 'Miền Bắc',
                ],
                '3D' => [
                    'A' => 'Miền Bắc',
                    'B' => 'Miền Bắc',
                    'C' => 'Miền Bắc',
                    'D' => 'Miền Bắc',
                    'LO' => 'Miền Bắc',
                ],
            ],
        ],
        'wednesday' => [
            'evening' => [
                '2D' => [
                    'A' => 'Đồng Nai',
                    'B' => 'Đồng Nai',
                    'C' => 'Đồng Nai',
                    'D' => 'Đồng Nai',
                    'LO' => 'Đồng Nai',
                    'N' => 'Sóc Trăng',
                    'HO' => 'Sóc Trăng',
                    'I' => 'Cần Thơ',
                ],
                '3D' => [
                    'A' => 'Đồng Nai',
                    'B' => 'Đồng Nai',
                    'C' => 'Đồng Nai',
                    'D' => 'Đồng Nai',
                    'LO' => 'Đồng Nai',
                    'N' => 'Sóc Trăng',
                    'HO' => 'Sóc Trăng',
                    'I' => 'Cần Thơ',
                ],
            ],
            'night' => [
                '2D' => [
                    'A' => 'Miền Bắc',
                    'B' => 'Miền Bắc',
                    'C' => 'Miền Bắc',
                    'D' => 'Miền Bắc',
                    'LO' => 'Miền Bắc',
                ],
                '3D' => [
                    'A' => 'Miền Bắc',
                    'B' => 'Miền Bắc',
                    'C' => 'Miền Bắc',
                    'D' => 'Miền Bắc',
                    'LO' => 'Miền Bắc',
                ],
            ],
        ],
        'thursday' => [
            'evening' => [
                '2D' => [
                    'A' => 'Tây Ninh',
                    'B' => 'Tây Ninh',
                    'C' => 'Tây Ninh',
                    'D' => 'Tây Ninh',
                    'LO' => 'Tây Ninh',
                    'N' => 'Bình Thuận',
                    'HO' => 'Bình Thuận',
                    'I' => 'An Giang',
                ],
                '3D' => [
                    'A' => 'Tây Ninh',
                    'B' => 'Tây Ninh',
                    'C' => 'Tây Ninh',
                    'D' => 'Tây Ninh',
                    'LO' => 'Tây Ninh',
                    'N' => 'Bình Thuận',
                    'HO' => 'Bình Thuận',
                    'I' => 'An Giang',
                ],
            ],
            'night' => [
                '2D' => [
                    'A' => 'Miền Bắc',
                    'B' => 'Miền Bắc',
                    'C' => 'Miền Bắc',
                    'D' => 'Miền Bắc',
                    'LO' => 'Miền Bắc',
                ],
                '3D' => [
                    'A' => 'Miền Bắc',
                    'B' => 'Miền Bắc',
                    'C' => 'Miền Bắc',
                    'D' => 'Miền Bắc',
                    'LO' => 'Miền Bắc',
                ],
            ],
        ],
        'friday' => [
            'evening' => [
                '2D' => [
                    'A' => 'Vĩnh Long',
                    'B' => 'Vĩnh Long',
                    'C' => 'Vĩnh Long',
                    'D' => 'Vĩnh Long',
                    'LO' => 'Vĩnh Long',
                    'N' => 'Vĩnh Long',
                    'HO' => 'Vĩnh Long',
                    'I' => 'Vĩnh Long',
                ],
                '3D' => [
                    'A' => 'Vĩnh Long',
                    'B' => 'Vĩnh Long',
                    'C' => 'Vĩnh Long',
                    'D' => 'Vĩnh Long',
                    'LO' => 'Vĩnh Long',
                    'N' => 'Vĩnh Long',
                    'HO' => 'Vĩnh Long',
                    'I' => 'Vĩnh Long',
                ],
            ],
            'night' => [
                '2D' => [
                    'A' => 'Miền Bắc',
                    'B' => 'Miền Bắc',
                    'C' => 'Miền Bắc',
                    'D' => 'Miền Bắc',
                    'LO' => 'Miền Bắc',
                ],
                '3D' => [
                    'A' => 'Miền Bắc',
                    'B' => 'Miền Bắc',
                    'C' => 'Miền Bắc',
                    'D' => 'Miền Bắc',
                    'LO' => 'Miền Bắc',
                ],
            ],
        ],
        'saturday' => [
            'evening' => [
                '2D' => [
                    'A' => 'TP.HCM',
                    'B' => 'TP.HCM',
                    'C' => 'TP.HCM',
                    'D' => 'TP.HCM',
                    'LO' => 'TP.HCM',
                    'N' => 'Long An',
                    'HO' => 'Long An',
                    'I' => 'Long An',
                ],
                '3D' => [
                    'A' => 'TP.HCM',
                    'B' => 'TP.HCM',
                    'C' => 'TP.HCM',
                    'D' => 'TP.HCM',
                    'LO' => 'TP.HCM',
                    'N' => 'Long An',
                    'HO' => 'Long An',
                    'I' => 'Long An',
                ],
            ],
            'night' => [
                '2D' => [
                    'A' => 'Miền Bắc',
                    'B' => 'Miền Bắc',
                    'C' => 'Miền Bắc',
                    'D' => 'Miền Bắc',
                    'LO' => 'Miền Bắc',
                ],
                '3D' => [
                    'A' => 'Miền Bắc',
                    'B' => 'Miền Bắc',
                    'C' => 'Miền Bắc',
                    'D' => 'Miền Bắc',
                    'LO' => 'Miền Bắc',
                ],
            ],
        ],
        'sunday' => [
            'evening' => [
                '2D' => [
                    'A' => 'Tiền Giang',
                    'B' => 'Tiền Giang',
                    'C' => 'Tiền Giang',
                    'D' => 'Tiền Giang',
                    'LO' => 'Tiền Giang',
                    'N' => 'Kiên Giang',
                    'HO' => 'Kiên Giang',
                    'I' => 'Kiên Giang',
                ],
                '3D' => [
                    'A' => 'Tiền Giang',
                    'B' => 'Tiền Giang',
                    'C' => 'Tiền Giang',
                    'D' => 'Tiền Giang',
                    'LO' => 'Tiền Giang',
                    'N' => 'Kiên Giang',
                    'HO' => 'Kiên Giang',
                    'I' => 'Kiên Giang',
                ],
            ],
            'night' => [
                '2D' => [
                    'A' => 'Miền Bắc',
                    'B' => 'Miền Bắc',
                    'C' => 'Miền Bắc',
                    'D' => 'Miền Bắc',
                    'LO' => 'Miền Bắc',
                ],
                '3D' => [
                    'A' => 'Miền Bắc',
                    'B' => 'Miền Bắc',
                    'C' => 'Miền Bắc',
                    'D' => 'Miền Bắc',
                    'LO' => 'Miền Bắc',
                ],
            ],
        ],
    ],

    'channel_weight' => [
        'evening' => [
            '2D' => [
                'A' => 1,
                'B' => 1,
                'C' => 1,
                'D' => 1,
                'LO' => 15,
                'N' => 1,
                'HO' => 1,
                'I' => 1,
            ],
            '3D' => [
                'A' => 1,
                'B' => 1,
                'C' => 1,
                'D' => 1,
                'LO' => 15,
                'N' => 1,
                'HO' => 1,
                'I' => 1,
            ],
        ],
        'night' => [
            '2D' => [
                'A' => 4,
                'B' => 1,
                'C' => 1,
                'D' => 1,
                'LO' => 19,
            ],
            '3D' => [
                'A' => 3,
                'B' => 1,
                'C' => 1,
                'D' => 1,
                'LO' => 19,
            ],
        ],
    ],
];
