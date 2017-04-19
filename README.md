# socialite-integration

This is protocol for laravel 5.2 socialite integration

##Steps for execution

1. Clone the repository
2. php artisan key:generate
3. Run migrations
4. Update the composer
5. Define the below provider details for all social networks, for eg -

    'google' => [
            'client_id' => 'XXXX',
            'client_secret' => 'XXXX',
            'redirect' => 'your callback url',
    ],
    'facebook' => [
        'client_id' => 'XXXX',
        'client_secret' => 'XXXX',
        'redirect' => 'your callback url',
    ],
    ...
    ...
6. Done 
