<?php

declare(strict_types=1);

namespace App\Support\Installation;

/**
 * Define installation configuration fields and validation rules.
 */
class Fields
{
    /**
     * One catalog supplies form inputs and their server-side allowlist.
     *
     * @param int $step
     *
     * @return array<array-key, mixed>
     */
    public static function groups(int $step): array
    {
        return match ($step) {
            2 => [
                'database' => [
                    'DB_CONNECTION' => [
                        'select',
                        'mysql',
                        [
                            'mysql',
                            'mariadb',
                            'pgsql',
                        ],
                    ],
                    'DB_HOST'       => [
                        'text',
                        '127.0.0.1',
                    ],
                    'DB_PORT'       => [
                        'number',
                        '3306',
                    ],
                    'DB_DATABASE'   => [
                        'text',
                        'jobic',
                    ],
                    'DB_USERNAME'   => [
                        'text',
                        '',
                    ],
                    'DB_PASSWORD'   => [
                        'password',
                        '',
                    ],
                ],
            ],
            3 => [
                'redis' => [
                    'REDIS_HOST'     => [
                        'text',
                        '127.0.0.1',
                    ],
                    'REDIS_PORT'     => [
                        'number',
                        '6379',
                    ],
                    'REDIS_USERNAME' => [
                        'text',
                        '',
                    ],
                    'REDIS_PASSWORD' => [
                        'password',
                        '',
                    ],
                    'REDIS_SCHEME'   => [
                        'select',
                        'tcp',
                        [
                            'tcp',
                            'tls',
                        ],
                    ],
                    'REDIS_DB'       => [
                        'number',
                        '0',
                    ],
                    'REDIS_CACHE_DB' => [
                        'number',
                        '1',
                    ],
                ],
                'mail'  => self::mail(),
            ],
            5 => [
                'site'          => [
                    'APP_NAME' => [
                        'text',
                        'Jobic',
                    ],
                    'APP_URL'  => [
                        'url',
                        '',
                    ],
                ],
                'administrator' => [
                    'ADMIN_NAME'                  => [
                        'text',
                        '',
                    ],
                    'ADMIN_EMAIL'                 => [
                        'email',
                        '',
                    ],
                    'ADMIN_PASSWORD'              => [
                        'password',
                        '',
                    ],
                    'ADMIN_PASSWORD_confirmation' => [
                        'password',
                        '',
                    ],
                ],
            ],
            default => [],
        };
    }

    /**
     * Get the mail configuration field definitions.
     *
     * @return array<array-key, mixed>
     */
    public static function mail(): array
    {
        return [
            'MAIL_MAILER'       => [
                'select',
                'log',
                [
                    'log',
                    'smtp',
                ],
            ],
            'MAIL_SCHEME'       => [
                'select',
                'smtp',
                [
                    'smtp',
                    'smtps',
                ],
            ],
            'MAIL_HOST'         => [
                'text',
                '',
            ],
            'MAIL_PORT'         => [
                'number',
                '587',
            ],
            'MAIL_USERNAME'     => [
                'text',
                '',
            ],
            'MAIL_PASSWORD'     => [
                'password',
                '',
            ],
            'MAIL_FROM_ADDRESS' => [
                'email',
                '',
            ],
            'MAIL_FROM_NAME'    => [
                'text',
                '',
            ],
        ];
    }

    /**
     * Get the field definitions for an installation step.
     *
     * @param int $step
     *
     * @return array<array-key, mixed>
     */
    public static function fields(int $step): array
    {
        return array_merge([], ...array_values(self::groups($step)));
    }

    /**
     * Build validation rules from configuration field definitions.
     *
     * @param array<string, mixed> $fields
     *
     * @return array<string, mixed>
     */
    public static function rules(array $fields): array
    {
        $rules = [];
        $optional = [
            'DB_PASSWORD',
            'REDIS_USERNAME',
            'REDIS_PASSWORD',
            'MAIL_USERNAME',
            'MAIL_PASSWORD',
        ];
        foreach ($fields as $key => $field) {
            $rules[$key] = [in_array($key, $optional, true) ? 'nullable' : 'required'];
            if (str_starts_with($key, 'MAIL_') && $key !== 'MAIL_MAILER') {
                $rules[$key] = in_array($key, [
                    'MAIL_USERNAME',
                    'MAIL_PASSWORD',
                ], true) ? ['nullable'] : ['required_if:MAIL_MAILER,smtp', 'nullable'];
            }

            $rules[$key] = array_merge($rules[$key], match ($field[0]) {
                'select' => [
                    'string',
                    'in:' . implode(',', $field[2]),
                ],
                'boolean' => [
                    'boolean',
                ],
                'number' => [
                    'integer',
                    'min:' . (in_array($key, [
                        'REDIS_DB',
                        'REDIS_CACHE_DB',
                    ], true) ? '0' : '1'),
                    'max:65535',
                ],
                'email' => [
                    'string',
                    'email',
                    'max:254',
                ],
                'url' => [
                    'string',
                    'url:http,https',
                    'max:2048',
                ],
                default => [
                    'string',
                    'max:255',
                    'not_regex:/[\r\n\x00]/',
                ],
            });
        }

        if (isset($rules['ADMIN_PASSWORD'])) {
            $rules['ADMIN_PASSWORD'][] = 'min:12';
            $rules['ADMIN_PASSWORD'][] = 'confirmed';
        }

        return $rules;
    }

    /**
     * Get safe form values from the environment and field defaults.
     *
     * @param array<string, mixed> $fields
     * @param array<string, mixed> $environment
     *
     * @return array<string, mixed>
     */
    public static function values(array $fields, array $environment): array
    {
        $values = [];
        foreach ($fields as $key => $field) {
            $value = $environment[$key] ?? $field[1];
            $values[$key] = $field[0] === 'password' ? '' : (in_array($value, ['null', '(null)'], true) ? '' : $value);
            if ($field[0] === 'boolean') {
                $values[$key] = filter_var($value, FILTER_VALIDATE_BOOLEAN) ? '1' : '0';
            }
        }

        return $values;
    }
}
