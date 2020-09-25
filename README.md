# Laravel SMS

[![Latest Stable Version](https://poser.pugx.org/reedware/laravel-sms/v/stable)](https://packagist.org/packages/reedware/laravel-sms)
[![Laravel Version](https://img.shields.io/badge/Laravel-6.x--8.x-blue)](https://laravel.com/)
[![Build Status](https://travis-ci.com/tylernathanreed/laravel-sms.svg?branch=master)](https://travis-ci.com/tylernathanreed/laravel-sms)

- [Introduction](#introduction)
    - [Configuration](#configuration)
    - [Driver Prerequisites](#driver-prerequisites)
- [Generating Textables](#generating-textables)
- [Writing Textables](#writing-textables)
    - [Configuring the Sender](#configuring-the-sender)
    - [Configuring the View](#configuring-the-view)
    - [View Data](#view-data)
- [Sending Text Messages](#sending-text-messages)
    - [Closure Messages](#closure-messages)
    - [Queueing Messages](#queueing-messages)
- [Rendering Textables](#rendering-textables)
    - [Previewing Textables in the Browser](#previewing-textables-in-the-browser)
- [Localizing Textables](#localizing-textables)
- [SMS & Local Development](#sms-and-local-development)
- [Events](#events)

<a name="introduction"></a>
## Introduction

This package provides a clean, simple service that integrates with SMS drivers, allowing you to quickly get started sending mail through a local or cloud based service of your choice.

Several driver implementations have been offloaded into separate packages so that you only have to include the integration for the drivers you need. Only drivers that don't require third party packages are included by default.

<a name="supported-drivers"></a>
## Supported Drivers

* Array
* Email
* Log
* Twilio (requires [reedware/laravel-sms-twilio](https://github.com/tylernathanreed/laravel-sms-twilio))

This package was also built such that anyone can add or override existing drivers, so that you aren't limited by what is provided out of the box.

<a name="configuration"></a>
### Configuration

The sms services may be configured via the `sms` configuration file. Each sms provider configured within this file may have its own options and even its own unique "transport", allowing your application to use different sms services to send certain sms messages. For example, your application might use Twilo to send transactional text messages while using Nexmo/Vonage to send bulk text messages.

<a name="driver-prerequisites"></a>
### Driver Prerequisites

The API based drivers such as Nexmo and Zenvia require the Guzzle HTTP library, which may be installed via the Composer package manager:

    composer require guzzlehttp/guzzle

Additional drivers that require third party packages are not provided by default. You will need to install the driver specific package (which will include the third party dependencies for you). You can refer to the list of [Supported Drivers](#supported-drivers) to see which package you need to install.

#### Email Driver

To use the Email driver, first ensure that your mailer is set up correctly. You may use any mail driver integrated with Laravel.

#### Twilio Driver

To use the Twilio driver, first install the driver specific package:

    composer require reedware/laravel-sms-twilio

Then set the `default` option in your `config/sms.php` configuration file to `twilio`. Next, verify that your twilio provider configuration file contains the following options:

    'your-driver-name' => [
        'transport' => 'twilio',
        'account_sid' => 'your-twilio-account-sid',
        'auth_token' => 'your-twilio-auth-token'
    ],

If you are not using the "US" [Twilio region](https://www.twilio.com/docs/global-infrastructure/edge-locations/legacy-regions), you may define your region id in the provider configuration:

    'your-driver-name' => [
        'transport' => 'twilio',
        'account_sid' => 'your-twilio-account-sid',
        'auth_token' => 'your-twilio-auth-token',
        'region' => 'sg1' // singapore
    ],

Additionally, ssl host and peer verification is disabled by default. To enable this, you may include the verify flag in the provider configuration:

    'your-driver-name' => [
        'transport' => 'twilio',
        'account_sid' => 'your-twilio-account-sid',
        'auth_token' => 'your-twilio-auth-token',
        'verify' => true
    ],

Additional instructions can be found in the [Laravel SMS Twilio](https://github.com/tylernathanreed/laravel-sms-twilio) package documentation.

<a name="generating-textables"></a>
## Generating Textables

Each type of text message sent by your application is represented as a "textable" class. These classes will be stored in the `app/SMS` directory by default. You can generate a new textable using the `make:sms` command:

    php artisan make:sms OrderShipped

<a name="writing-textables"></a>
## Writing Textables

All of a textable class' configuration is done in the `build` method. Within this method, you may call various methods such as `from`, `to`, and `view` to configure the message's presentation and delivery.

<a name="configuring-the-sender"></a>
### Configuring the Sender

#### Using the `from` Method

First, let's explore configuring the sender of the text message. Or, in other words, who the message is going to be "from". There are two ways to configure the sender. First, you may use the `from` method within your textable class' `build` method:

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from('9995551234')
                    ->view('sms.orders.shipped');
    }

#### Using a Global `from` Address

However, if your application uses the same "from" number for all of its text messages, it can become cumbersome to call the `from` method in each textable class you generate. Instead, you may specify a global "from" number in your `config/sms.php` configuration file. This address will be used if no other "from" address is specified within the textable class:

    'from' => '9995551234',

Alternatively, you may also define a "from" number for the sms provider specifically:

    'your-driver-name' => [
        'transport' => 'email',
        'from' => '9995551234'
    ],

<a name="configuring-the-view"></a>
### Configuring the View

Within a textable class' `build` method, you may use the `view` method to specify which template should be used when rendering the text message's contents:

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('sms.orders.shipped');
    }

> You may wish to create a `resources/views/sms` directory to house all of your sms templates; however, you are free to place them wherever you wish within your `resources/views` directory.

#### Plain Text Messages

If you would like to define your message as pre-rendered text, you may use the `text` method. Unlink the `view` method, the `text` method the already rendered contents the message.

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->text('Your order has been shipped!');
    }

<a name="view-data"></a>
### View Data

#### Via Public Properties

Typically, you will want to pass some data to your view that you can utilize when rendering the text message. There are two ways you may make data available to your view. First, any public property defined on your textable class will automatically be made available to the view. So, for example, you may pass data into your textable class' constructor and set that data to public properties defined on the class:

    <?php

    namespace App\SMS;

    use App\Models\Order;
    use Illuminate\Bus\Queueable;
    use Illuminate\Queue\SerializesModels;
    use Reedware\LaravelSMS\Textable;

    class OrderShipped extends Textable
    {
        use Queueable, SerializesModels;

        /**
         * The order instance.
         *
         * @var \App\Models\Order
         */
        public $order;

        /**
         * Creates a new message instance.
         *
         * @param  \App\Models\Order  $order
         *
         * @return void
         */
        public function __construct(Order $order)
        {
            $this->order = $order;
        }

        /**
         * Builds the message.
         *
         * @return $this
         */
        public function build()
        {
            return $this->view('sms.orders.shipped');
        }
    }

Once the data has been set to a public property, it will automatically be available in your view, so you may access it like you would access any other data in your Blade templates:

    <div>
        Price: {{ $order->price }}
    </div>

> **Note:** A `$message` variable is always passed to sms views, so you should avoid passing a `message` variable in your view payload.

#### Via the `with` Method:

If you would like to customize the format of your text message's data before it is sent to the template, you may manually pass your data to the view via the `with` method. Typically, you will still pass data via the textable class' constructor; however, you should set this data to `protected` or `private` properties so the data is not automatically made available to the template. Then, when calling the `with` method, pass an array of data that you wish to make available to the template:

    <?php

    namespace App\SMS;

    use App\Models\Order;
    use Illuminate\Bus\Queueable;
    use Illuminate\Queue\SerializesModels;
    use Reedware\LaravelSMS\Textable;

    class OrderShipped extends Textable
    {
        use Queueable, SerializesModels;

        /**
         * The order instance.
         *
         * @var \App\Models\Order
         */
        protected $order;

        /**
         * Creates a new message instance.
         *
         * @param  \App\Models\Order $order
         *
         * @return void
         */
        public function __construct(Order $order)
        {
            $this->order = $order;
        }

        /**
         * Builds the message.
         *
         * @return $this
         */
        public function build()
        {
            return $this->view('sms.orders.shipped')
                        ->with([
                            'orderName' => $this->order->name,
                            'orderPrice' => $this->order->price,
                        ]);
        }
    }

Once the data has been passed to the `with` method, it will automatically be available in your view, so you may access it like you would access any other data in your Blade templates:

    <div>
        Price: {{ $orderPrice }}
    </div>

<a name="sending-text-messages"></a>
## Sending Text Messages

To send a message, use the `to` method on the `SMS` facade. The `to` method accepts a phone number, a user instance, or a collection of users. If you pass an object or collection of objects, the sms provider will automatically use their `number` and `carrier` properties when setting the sms recipients, so make sure these attributes are available on your objects. Once you have specified your recipients, you may pass an instance of your textable class to the `send` method:

    <?php

    namespace App\Http\Controllers;

    use App\Http\Controllers\Controller;
    use App\Models\Order;
    use App\SMS\OrderShipped;
    use Illuminate\Http\Request;
    use Reedware\LaravelSMS\SMS;

    class OrderController extends Controller
    {
        /**
         * Ships the given order.
         *
         * @param  \Illuminate\Http\Request  $request
         * @param  integer                   $orderId
         *
         * @return \Illuminate\Http\Response
         */
        public function ship(Request $request, $orderId)
        {
            $order = Order::findOrFail($orderId);

            // Ship order...

            SMS::to($request->user())->send(new OrderShipped($order));
        }
    }

#### Looping Over Recipients

Occasionally, you may need to send a textable to a list of recipients by iterating over an array of recipients / phone numbers. Since the `to` method appends phone numbers to the textable's list of recipients, you should always re-create the textable instance for each recipient:

    foreach (['9995551234', '9995556789'] as $recipient) {
        SMS::to($recipient)->send(new OrderShipped($order));
    }

#### Sending Text Messages via a Specific Provider

By default, the sms provider configured as the `default` provider in your `sms` configuration file will be used. However, you may use the `provider` method to send a message using a specific provider configuration:

    SMS::provider('twilio')
        ->to($request->user())
        ->send(new OrderShipped($order));

<a name="closure-messages"></a>
### Closure Messages

If using textables isn't something you want to do, you can also send text messages by using a closure implementation. To send a closure-based message, use the `send` method on the `SMS` facade, and provide it three arguments. First, the name of a `view` that contains the text messages. Secondly, an array of data that you wish to pass to the view. Lastly, a `Closure` callback which receives a message instance, allowing you to customize the recipients, body, and other aspects of the sms message:

    <?php

    namespace App\Http\Controllers;

    use App\Http\Controllers\Controller;
    use App\Models\Order;
    use Illuminate\Http\Request;
    use Reedware\LaravelSMS\SMS;

    class OrderController extends Controller
    {
        /**
         * Ships the given order.
         *
         * @param  \Illuminate\Http\Request  $request
         * @param  integer                   $orderId
         *
         * @return \Illuminate\Http\Response
         */
        public function ship(Request $request, $orderId)
        {
            $order = Order::findOrFail($orderId);

            // Ship order...

            SMS::send('sms.orders.shipped', ['order' => $order], function($m) use ($request) {
                $m->to($request()->user->number);
            });
        }
    }

If you want to send a plain message instead, you can instead use the `raw` method on the `SMS` facade:

    SMS::raw('Your order has been shipped!', function($m) use ($request) {
        $m->to($request()->user->number);
    });

<a name="queueing-messages"></a>
### Queueing Messages

#### Queueing a Text Message

Since sending text messages can drastically lengthen the response time of your application, many developers choose to queue text messages for background sending. This is made easy using Laravel's built-in queues. To queue an sms message, use the `queue` method on the `SMS` facade after specifying the message's recipients:

    SMS::to($request->user())
        ->queue(new OrderShipped($order));

This method will automatically take care of pushing a job onto the queue so the message is sent in the background. You will need to configure your queues before using this feature.

#### Delayed Message Queueing

If you wish to delay the delivery of a queued sms message, you may use the `later` method. As its first argument, the `later` method accepts a `DateTime` instance indicating when the message should be sent:

    $when = now()->addMinutes(10);

    SMS::to($request->user())
        ->later($when, new OrderShipped($order));

#### Pushing to Specific Queues

Since all textable classes generated using the `make:sms` command make use of the `Illuminate\Bus\Queueable` trait, you may call the `onQueue` and `onConnection` methods on any textable class instance, allowing you to specify the connection and queue name for the message:

    $message = (new OrderShipped($order))
        ->onConnection('sqs')
        ->onQueue('sms');

    SMS::to($request->user())
        ->queue($message);

#### Queueing By Default

If you have textable classes that you want to always be queued, you may implement the `ShouldQueue` contract on the class. Now, even if you call the `send` method when texting, the textable will still be queued since it implements the contract:

    use Illuminate\Contracts\Queue\ShouldQueue;

    class OrderShipped extends Textable implements ShouldQueue
    {
        //
    }

<a name="rendering-textables"></a>
## Rendering Textables

Sometimes you may wish to capture the message content of a textable without sending it. To accomplish this, you may call the `render` method of the textable. This method will return the evaluated contents of the textable as a string:

    $invoice = App\Models\Invoice::find(1);

    return (new App\SMS\InvoicePaid($invoice))->render();

<a name="previewing-textables-in-the-browser"></a>
### Previewing Textables in the Browser

When designing a textable's template, it is convenient to quickly preview the rendered textable in your browser like a typical Blade template. For this reason, you are allowed to return any textable directly from a route Closure or controller. When a textable is returned, it will be rendered and displayed in the browser, allowing you to quickly preview its design without needing to send it to an actual cellular device:

    Route::get('textable', function () {
        $invoice = App\Models\Invoice::find(1);

        return new App\SMS\InvoicePaid($invoice);
    });

<a name="localizing-textable"></a>
## Localizing Textables

You can also send textable in a locale other than the current language, and the locale will even be remembered if the text message is queued.

To accomplish this, the `SMS` facade offers a `locale` method to set the desired language. The application will change into this locale when the textable is being formatted and then revert back to the previous locale when formatting is complete:

    SMS::to($request->user())->locale('es')->send(
        new OrderShipped($order)
    );

### User Preferred Locales

Sometimes, applications store each user's preferred locale. By implementing the `HasLocalePreference` contract on one or more of your models, you may instruct the application to use this stored locale when sending text messages:

    use Illuminate\Contracts\Translation\HasLocalePreference;

    class User extends Model implements HasLocalePreference
    {
        /**
         * Returns the user's preferred locale.
         *
         * @return string
         */
        public function preferredLocale()
        {
            return $this->locale;
        }
    }

Once you have implemented the interface, the preferred locale will automatically be used when sending textables and notifications to the model. Therefore, there is no need to call the `locale` method when using this interface:

    SMS::to($request->user())->send(new OrderShipped($order));

<a name="sms-and-local-development"></a>
## SMS & Local Development

When developing an application that sends text messages, you probably don't want to actually send texts to live cellular devices. There are several ways to "disable" the actual sending of text messages during local development.

#### Log Driver

Instead of sending your text messages, the `log` sms driver will write all text messages to your log files for inspection.

#### Universal To

Another solution is to set a universal recipient of all text messages sent by the application. This way, all the text messages generated by your application will be sent to a specific phone number, instead of the phone number actually specified when sending the message. This can be done via the `to` option in your `config/sms.php` configuration file:

    'to' => [
        'number' => '9995551234',
        'carrier' => 'Example'
    ],

<a name="events"></a>
## Events

Two events are fired during the process of sending text messages. The `MessageSending` event is fired prior to a message being sent, while the `MessageSent` event is fired after a message has been sent. Remember, these events are fired when the text message is being *sent*, not when it is queued. You may register an event listener for this event in your `EventServiceProvider`:

    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        'Reedware\LaravelSMS\Events\MessageSending' => [
            'App\Listeners\LogSendingMessage',
        ],
        'Reedware\LaravelSMS\Events\MessageSent' => [
            'App\Listeners\LogSentMessage',
        ],
    ];
