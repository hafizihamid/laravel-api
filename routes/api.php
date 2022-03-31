<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group(
    ['namespace' => 'Api', 'as' => 'api.', 'prefix' => 'v1'],
    function () {
        Route::post('/login', 'CustomerAuthController@login')->name('login');
        Route::post('/register', 'CustomerAuthController@register')->name('register');

        //give two times more atteempt for api call... another throttle for sms is already in place
        $otpThrottle = (config("staticdata.throttle.otp.attempt") * 2) . "," . config("staticdata.throttle.otp.decay") . ",otp";
        Route::post('/sendOTP', 'CustomerAuthController@sendOTP')->name('sendOTP')->middleware("throttle_customer:$otpThrottle");
        Route::post('/verifyOTP', 'CustomerAuthController@verifyOTP')->name('verifyOTP');
        Route::post('/reset', 'CustomerAuthController@reset')->name('reset');

        //route for both customer and admin
        Route::group(
            ['middleware' => ['auth:customer,api']],
            function () {
                Route::group(
                    ['prefix' => 'lookup', 'as' => 'lookup.'],
                    function () {
                        Route::get('/province/{id?}', 'LookupController@provinceList')->name('province');
                        Route::get('/city/{region_id?}', 'LookupController@cityList')->name('city');
                        Route::get('/location/{province_id?}', 'LookupController@locationList')->name('location');
                        Route::get('/vaccine-type/{warehouse_location_id?}', 'LookupController@vaccineTypeList')->name('vaccine_type');
                        Route::get('/warehouse-location', 'LookupController@warehouseList')->name('warehouse')->middleware('permission:view-all-warehouse-locations|view-warehouse-locations|add-vaccine-type|add-inventory-stock-in|add-inventory-stock-out|add-inventory-stock-disposal|add-inventory-stock-count,api');
                        Route::get('/lgu', 'LookupController@lguList')->name('lgu');
                    }
                );
                Route::group(
                    ['prefix' => 'booking', 'as' => 'booking.'],
                    function () {
                        Route::post('slot-left', 'BookingController@getSlotLeft')->name('slot_left');
                        Route::put('/{id}', 'BookingController@updateStatus')->name('update')->middleware('scope:admin,customer');
                    }
                );
                Route::group(
                    ['prefix' => 'vaccine', 'as' => 'vaccine.'],
                    function () {
                        Route::post('allocate/{id}', 'VaccineController@allocate')->name('allocate')->middleware('scope:admin');
                    }
                );


                Route::group(
                    ['prefix' => 'vaccine-passport', 'as' => 'vaccine-passport.'],
                    function () {
                        Route::post('generate', 'VaccinePassportController@generate')->name('generate')->middleware('scope:customer');
                        Route::post('validate', 'VaccinePassportController@validate')->name('validate')->middleware('scope:admin');
                    }
                );

                Route::group(
                    ['prefix' => 'feedback', 'as' => 'feedback.'],
                    function () {
                        Route::get('/', 'FeedbackController@getQuestionList')->name('list');
                        Route::post('/', 'FeedbackController@postFeedback')->name('submit');
                    }
                );
            }
        );

        Route::group(
            ['middleware' => ['auth:customer', 'scope:customer', 'verified']],
            function () {
                Route::get('/authcheck', 'CustomerAuthController@authCheck')->name('auth-check');
                Route::get('/logout', 'CustomerAuthController@logout')->name('logout');

                Route::group(
                    ['prefix' => 'applicant-detail', 'as' => 'applicant-detail.'],
                    function () {
                        Route::post('/first-check', 'ApplicantController@firstCheck')->name('first_check');
                        Route::get('/options', 'ApplicantController@applicantDetailOptions')->name('options');
                        Route::post('/submit', 'ApplicantController@applicantDetailSubmit')->name('submit');
                        Route::get('/view', 'ApplicantController@applicantDetailView')->name('view');
                        Route::post('/update', 'ApplicantController@applicantDetailUpdate')->name('update');
                    }
                );

                Route::group(
                    ['prefix' => 'certificate', 'as' => 'certificate.'],
                    function () {
                        Route::post('/', 'CertificateController@customerCheck')->name('check');
                    }
                );

                Route::group(
                    ['prefix' => 'booking', 'as' => 'booking.'],
                    function () {
                        Route::post('/', 'BookingController@getCustomerList')->name('list');
                        Route::get('/{id}/details', 'BookingController@getCustomerDetails')->name('details');
                        Route::post('/{id}/request-new-bookdate', 'BookingController@requestNewBookDate')->name('request_new_bookdate');
                        Route::post('/book', 'BookingController@book')->name('book');
                        Route::get('/active', 'BookingController@activeBooking')->name('active');
                    }
                );
            }
        );

        // ------- ADMIN API ----------
        Route::group(
            ['prefix' => 'admin', 'as' => 'admin.'],
            function () {
                Route::post('/login', 'AuthController@login')->name('login');
                Route::group(
                    ['prefix' => 'password', 'as' => 'password.'],
                    function () {
                        Route::post('/forgot', 'AuthController@forgot')->name('forgot');
                        Route::post('/reset', 'AuthController@reset')->name('reset');
                        Route::post('/set', 'UserController@setPassword')->name('set');
                    }
                );
                Route::group(
                    ['middleware' => ['auth:api', 'scope:admin']],
                    function () {
                        Route::get('/authcheck', 'AuthController@authCheck')->name('auth-check');
                        Route::get('/logout', 'AuthController@logout')->name('logout');

                        Route::group(
                            ['prefix' => 'vaccine-type', 'as' => 'vaccine-type.'],
                            function () {
                                Route::get('/', 'VaccineTypeController@list')->name('list')->middleware('permission:view-vaccine-type|view-all-vaccine-type');
                                Route::post('/add', 'VaccineTypeController@store')->name('create')->middleware('permission:add-vaccine-type|add-all-vaccine-type');
                                Route::delete('/{id}/delete', 'VaccineTypeController@delete')->name('delete')->middleware('permission:delete-vaccine-type|delete-all-vaccine-type');
                                Route::post('/{id}/update', 'VaccineTypeController@update')->name('update')->middleware('permission:update-vaccine-type|update-all-vaccine-type');
                                Route::get('/{id}/details', 'VaccineTypeController@details')->name('details')->middleware('permission:view-vaccine-type-details|view-all-vaccine-type-details');
                            }
                        );

                        Route::group(
                            ['prefix' => 'off-day', 'as' => 'off-day.'],
                            function () {
                                Route::get('/{location_id}', 'OffDayController@list')->name('list');
                                Route::post('/add', 'OffDayController@create')->name('create');
                            }
                        );

                        Route::group(
                            ['prefix' => 'warehouse-location', 'as' => 'warehouse-location.'],
                            function () {
                                Route::group(
                                    ['prefix' => '/user-staff'],
                                    function () {
                                        Route::get('/', 'WarehouseLocationController@listUserStaff')->name('list-user-staff')->middleware('permission:view-all-warehouse-locations-user-staff|view-warehouse-locations-user-staff');
                                        Route::post('/add', 'WarehouseLocationController@createUserStaff')->name('create-user-staff')->middleware('permission:create-warehouse-locations-user-staff');
                                        Route::get('/{user_id}', 'WarehouseLocationController@viewUserStaff')->name('view-user-staff')->middleware('permission:view-all-warehouse-locations-user-staff-details|view-warehouse-locations-user-staff-details');
                                        Route::post('/{user_id}', 'WarehouseLocationController@updateUserStaff')->name('update-user-staff')->middleware('permission:update-warehouse-locations-user-staff');
                                        Route::delete('/{user_id}', 'WarehouseLocationController@destroyUserStaff')->name('delete-user-staff')->middleware('permission:delete-warehouse-locations-user-staff');
                                    }
                                );

                                Route::post('/', 'WarehouseLocationController@createWarehouseLocation')->name('create')->middleware('permission:create-warehouse-locations');
                                Route::get('/', 'WarehouseLocationController@getWarehouseLocation')->name('list')->middleware('permission:view-warehouse-locations|view-all-warehouse-locations');
                                Route::get('/{id}', 'WarehouseLocationController@getWarehouseLocationDetails')->name('view')->middleware('permission:view-warehouse-locations-details|view-all-warehouse-locations-details');
                                Route::post('/{id}/update', 'WarehouseLocationController@updateWarehouseLocation')->name('update')->middleware('permission:update-warehouse-locations');
                                Route::delete('/{id}', 'WarehouseLocationController@deleteWarehouseLocation')->name('delete')->middleware('permission:delete-warehouse-locations');
                            }
                        );


                        Route::group(
                            ['prefix' => 'special-slot', 'as' => 'special-slot.'],
                            function () {
                                Route::get('/{id}', 'SpecialSlotController@list')->name('list');
                                Route::post('/', 'SpecialSlotController@create')->name('create');
                                Route::get('/{id}/details', 'SpecialSlotController@details')->name('details');
                                Route::post('/{id}/update', 'SpecialSlotController@update')->name('update');
                                Route::delete('/{id}', 'SpecialSlotController@delete')->name('delete');
                            }
                        );
                        Route::resource('location', 'LocationController');

                        Route::group(
                            ['prefix' => 'location', 'as' => 'location.'],
                            function () {
                                Route::post('/{id}/create-user-staff', 'LocationController@createUserStaff')->name('create-user-staff');
                                Route::get('/{id}/list-user-staff', 'LocationController@listUserStaff')->name('list-user-staff');

                                Route::group(
                                    ['prefix' => '/user-staff'],
                                    function () {
                                        Route::get('/{user_id}', 'LocationController@viewUserStaff')->name('view-user-staff');
                                        Route::post('/{user_id}', 'LocationController@updateUserStaff')->name('update-user-staff');
                                        Route::delete('/{user_id}', 'LocationController@destroyUserStaff')->name('delete-user-staff');
                                    }
                                );
                            }
                        );

                        Route::resource('role', 'RoleController');

                        Route::group(
                            ['prefix' => 'booking', 'as' => 'booking.'],
                            function () {
                                Route::get('/', 'BookingController@list')->name('list')->middleware('permission:view-bookings|view-all-bookings');
                                Route::get('/{id}/details', 'BookingController@details')->name('details')->middleware('permission:view-bookings-details|view-all-bookings-details');
                                Route::get('details/{user_id}', 'BookingController@details')->name('details_by_user_id')->middleware('permission:view-bookings-details|view-all-bookings-details');
                                Route::post('/{id}/update', 'BookingController@update')->name('update')->middleware('permission:update-bookings|update-all-bookings');
                            }
                        );

                        Route::group(
                            ['prefix' => 'permissions', 'as' => 'permissions.'],
                            function () {
                                Route::get('/', 'PermissionController@list')->name('list')->middleware('permission:list-permissions');
                            }
                        );

                        Route::group(
                            ['prefix' => 'dashboard', 'as' => 'dashboard.'],
                            function () {
                                Route::get('/', 'DashboardController@index')->name('index')->middleware('permission:dashboard-vaccine-inventory');
                                Route::get('/appointment-vaccine', 'DashboardController@getAppointmentVaccine')->name('appointment-vaccine')->middleware('permission:dashboard-appointment-vaccine');
                            }
                        );

                        Route::group(
                            ['prefix' => 'user', 'as' => 'user.'],
                            function () {
                                Route::get('/', 'UserController@index')->name('index')->middleware('permission:view-users');
                                Route::post('/add', 'UserController@store')->name('store')->middleware('permission:add-users');
                                Route::post('/disable/{id}', 'UserController@disable')->name('disable')->middleware('permission:disable-users');
                                Route::post('/update/{id}', 'UserController@update')->name('update')->middleware('permission:update-users');
                                Route::get('/show/{id}', 'UserController@show')->name('show')->middleware('permission:view-users-details');
                            }
                        );

                        Route::group(
                            ['prefix' => 'password', 'as' => 'password.'],
                            function () {
                                Route::post('/change', 'UserController@changePassword')->name('change');
                            }
                        );

                        Route::group(
                            ['prefix' => 'inventory', 'as' => 'inventory.'],
                            function () {
                                Route::get('/stock-in', 'InventoryController@stockInList')->name('stock-in.list')->middleware('permission:view-inventory-stock-in|view-all-inventory-stock-in');
                                Route::post('/stock-in/add', 'InventoryController@stockInCreate')->name('stock-in.create')->middleware('permission:add-inventory-stock-in|add-all-inventory-stock-in');
                                Route::get('/stock-in/{id}/details', 'InventoryController@stockInDetails')->name('stock-in.details')->middleware('permission:view-inventory-stock-in-details|view-all-inventory-stock-in-details');

                                Route::get('/stock-out', 'InventoryController@stockOutList')->name('stock-out.list')->middleware('permission:view-inventory-stock-out|view-all-inventory-stock-out');
                                Route::post('/stock-out/add', 'InventoryController@stockOutCreate')->name('stock-out.create')->middleware('permission:add-inventory-stock-out|add-all-inventory-stock-out');
                                Route::post('/stock-out/{order_ref_no}/update', 'InventoryController@stockOutUpdate')->name('stock-out.update')->middleware('permission:update-inventory-stock-out|update-all-inventory-stock-out');
                                Route::get('/stock-out/{order_ref_no}/details', 'InventoryController@stockOutDetails')->name('stock-out.details')->middleware('permission:view-inventory-stock-out-details|view-all-inventory-stock-out-details');

                                Route::get('/stock-disposal', 'InventoryController@stockDisposalList')->name('stock-disposal.list')->middleware('permission:view-inventory-stock-disposal|view-all-inventory-stock-disposal');
                                Route::post('/stock-disposal/add', 'InventoryController@stockDisposalCreate')->name('stock-disposal.create')->middleware('permission:add-inventory-stock-disposal|add-all-inventory-stock-disposal');
                                Route::get('/stock-disposal/{id}/details', 'InventoryController@stockDisposalDetails')->name('stock-disposal.details')->middleware('permission:view-inventory-stock-disposal-details|view-all-inventory-stock-disposal-details');

                                Route::get('/stock-count', 'InventoryController@stockCountList')->name('stock-count.list')->middleware('permission:view-inventory-stock-count|view-all-inventory-stock-count');
                                Route::post('/stock-count/add', 'InventoryController@stockCountCreate')->name('stock-count.create')->middleware('permission:add-inventory-stock-count|add-all-inventory-stock-count');
                                Route::get('/stock-count/{id}/details', 'InventoryController@stockCountDetails')->name('stock-count.details')->middleware('permission:view-inventory-stock-count-details|view-all-inventory-stock-count-details');
                            }
                        );

                        Route::group(
                            ['prefix' => 'vaccine', 'as' => 'vaccine.'],
                            function () {
                                Route::post('get-count-by-location', 'VaccineController@getCountByLocation')->name('get_count_by_location')->middleware('permission:get-vaccine-count-by-location');
                                Route::post('check-serial-no', 'VaccineController@checkSerialNo')->name('check_serial_no')->middleware('permission:check-vaccine-serial-no');
                                Route::post('get-vaccination-done', 'VaccineController@getVaccinationDone')->name('get_vaccination_done')->middleware('permission:get-vaccination-done');
                            }
                        );

                        Route::group(
                            ['prefix' => 'feedback', 'as' => 'feedback.'],
                            function () {
                                Route::get('/list', 'FeedbackController@getFeedbackList')->name('list')->middleware('permission:view-feedback-list');
                            }
                        );

                        Route::group(
                            ['prefix' => 'demand-forecast', 'as' => 'demand-forecast.'],
                            function () {
                                Route::get('/{province_id}', 'DemandForecastController@getDemandForecast')->name('list')->middleware('permission:view-report-demand-forecast');
                                Route::post('/{province_id}', 'DemandForecastController@updateDemandForecast')->name('update')->middleware('permission:update-report-demand-forecast');
                            }
                        );

                        Route::group(
                            ['prefix' => 'report', 'as' => 'report.'],
                            function () {
                                Route::get('/{type}', 'ReportController@getReport')->name('get'); //permission middelware in code
                            }
                        );
                    }
                );
            }
        );
    }
);


// ----------- V2 API ------------
Route::group(
    ['namespace' => 'Api', 'as' => 'apiv2.', 'prefix' => 'v2'],
    function () {
        Route::post('/payment/direct', 'PaymentController@direct')->name('payment.direct');
        Route::post('/payment/indirect', 'PaymentController@indirect')->name('payment.indirect');

        //route for both customer and admin
        Route::group(
            ['middleware' => ['auth:customer,api']],
            function () {
                Route::group(
                    ['prefix' => 'lookup', 'as' => 'lookup.'],
                    function () {
                        Route::get('/address', 'LookupController@v2Address')->name('address');
                    }
                );
            }
        );

        Route::group(
            ['prefix' => 'admin', 'as' => 'admin.'],
            function () {
                Route::group(
                    ['middleware' => ['auth:api', 'scope:admin']],
                    function () {
                        Route::group(
                            ['prefix' => 'vaccine', 'as' => 'vaccine.'],
                            function () {
                                Route::post('verify-vaccine', 'VaccineController@v2VerifyVaccine')->name('verify_vaccine')->middleware('permission:check-vaccine-serial-no');
                                Route::post('allocate', 'VaccineController@v2Allocate')->name('allocate');
                            }
                        );

                        Route::group(
                            ['prefix' => 'doctor', 'as' => 'doctor.'],
                            function () {
                                Route::get('/', 'DoctorController@getDoctorList')->name('doctor-list')->middleware('permission:view-doctor-list');
                            }
                        );

                        Route::group(
                            ['prefix' => 'booking', 'as' => 'booking.'],
                            function () {
                                Route::get('/{ref_no}/details', 'BookingController@v2Details')->name('details')->middleware('permission:view-bookings-details|view-all-bookings-details');
                                Route::post('/update-status/{id}', 'BookingController@v2UpdateStatus')->name('update_status')->middleware('permission:update-booking-status');
                            }
                        );

                        Route::group(
                            ['prefix' => 'slot-allocate', 'as' => 'slot-allocation.'],
                            function () {
                                Route::get('/', 'SlotAllocationController@getAllocationList')->name('list')->middleware('permission:view-slot-allocation');
                                Route::get('/{lgu_id}', 'SlotAllocationController@getAllocationStatus')->name('status')->middleware('permission:view-slot-allocation');
                                Route::post('/set', 'SlotAllocationController@setAllocation')->name('set')->middleware('permission:set-slot-allocation');
                            }
                        );

                        Route::group(
                            ['prefix' => 'general-settings', 'as' => 'general-settings.'],
                            function () {
                                Route::get('/', 'GeneralSettingController@listSettings')->name('setting-list')->middleware('permission:view-general-settings');
                                Route::post('/update', 'GeneralSettingController@updateSettings')->name('update')->middleware('permission:update-general-settings');
                            }
                        );
                    }
                );
            }
        );

        Route::group(
            ['middleware' => 'sso'],
            function () {
                Route::group(
                    ['prefix' => 'lookup-sso', 'as' => 'lookup-sso.'],
                    function () {
                        Route::get('/address', 'LookupController@v2Address')->name('address');
                    }
                );

                Route::group(
                    ['prefix' => 'payment'],
                    function () {
                        Route::post('/paymentchannel', 'PaymentController@paymentChannel')->name('payment.get.paymentchannel');
                        Route::post('/postpayment', 'PaymentController@postPayment')->name('payment.postpayment');
                        Route::post('/paymentstatus', 'PaymentController@paymentStatus')->name('payment.get.payment_status');
                    }
                );

                Route::group(
                    ['prefix' => 'booking', 'as' => 'booking.'],
                    function () {
                        Route::post('/book', 'BookingController@v2book')->name('book');
                        Route::get('/options', 'BookingController@v2BookingOptions')->name('options');
                    }
                );
            }
        );
    }
);
