<?php

return [
    'states' => ['required', 'optional', 'hidden'],

    'groups' => [
        'financial' => [
            'title' => 'بخش مالی و فیش سازمان',
            'icon' => '💳',
            'fields' => [
                'receipt_code' => [
                    'label' => 'شماره فیش سازمان',
                    'type' => 'text',
                    'rules' => ['string', 'max:100'],
                ],
                'receipt_amount' => [
                    'label' => 'مبلغ واریزی (ریال)',
                    'type' => 'number',
                    'rules' => ['numeric', 'min:0'],
                ],
                'receipt_file' => [
                    'label' => 'تصویر فیش',
                    'type' => 'file',
                ],
            ],
        ],
        'cmr' => [
            'title' => 'بخش بارنامه (CMR)',
            'icon' => '📄',
            'fields' => [
                'trip_code' => [
                    'label' => 'کد سفر (با J)',
                    'type' => 'text',
                    'rules' => ['string', 'max:100', 'regex:/^j/i'],
                ],
                'cmr_date' => [
                    'label' => 'تاریخ بارگیری CMR',
                    'type' => 'date',
                    'rules' => ['date_format:Y-m-d'],
                ],
                'cmr_file' => [
                    'label' => 'تصویر CMR',
                    'type' => 'file',
                ],
            ],
        ],
        'tir' => [
            'title' => 'بخش کارنه تیر',
            'icon' => '📘',
            'fields' => [
                'tir_carnet_number' => [
                    'label' => 'شماره کارنه تیر',
                    'type' => 'text',
                    'rules' => ['string', 'max:100'],
                ],
                'tir_carnet_date' => [
                    'label' => 'تاریخ کارنه تیر',
                    'type' => 'date',
                    'rules' => ['date_format:Y-m-d'],
                ],
                'tir_file' => [
                    'label' => 'تصویر کارنه تیر',
                    'type' => 'file',
                ],
            ],
        ],
        'declaration' => [
            'title' => 'بخش اظهارنامه',
            'icon' => '📝',
            'fields' => [
                'declaration_file' => [
                    'label' => 'تصویر اظهارنامه',
                    'type' => 'file',
                ],
            ],
        ],
    ],
];
