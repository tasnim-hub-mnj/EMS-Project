<?php

use App\Http\Controllers\BoothBookingController;
use App\Http\Controllers\BoothController;
use App\Http\Controllers\BoothManagementController;
use App\Http\Controllers\BoothReviewController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\CollectedBoothController;
use App\Http\Controllers\CompanyProfileController;
use App\Http\Controllers\DashboardInvestorController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\EventsController;
use App\Http\Controllers\ExhibitionController;
use App\Http\Controllers\ExhibitionReviewController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\FavoritesController;
use App\Http\Controllers\InvestorController;
use App\Http\Controllers\ProfileCompanyController;
use App\Http\Controllers\ProfileVisitorController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\SponsorshipBookingController;
use App\Http\Controllers\SponsorEventController;
use App\Http\Controllers\SupportTicketController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VisitorController;
use App\Http\Controllers\VisitorScheduleController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

//================================================================
//***********************Investor*********************************
//================================================================
//RegisterController
Route::post('/auth/register', [InvestorController::class, 'register']);// تسجيل حساب جديد
//AuthController
Route::post('/auth/verify-otp', [InvestorController::class, 'verifyOtp']);// التحقق من OTP بعد التسجيل
Route::post('/auth/resend-otp', [InvestorController::class, 'resendOtp']);// إعادة إرسال OTP بعد التسجيل
//LoginController
Route::post('/auth/login', [InvestorController::class, 'login']);// تسجيل الدخول

//ForgotPasswordController
Route::post('/auth/forgot-password', [InvestorController::class, 'forgotPassword1']);// الخطوة 1: إرسال OTP
Route::post('/auth/forgot-password/verify-otp', [InvestorController::class, 'forgotPassword2']);// الخطوة 2: التحقق من OTP
Route::post('/auth/reset-password', [InvestorController::class, 'resetPassword']);// الخطوة 3: تعيين كلمة مرور جديدة

Route::middleware('auth:sanctum')->group(function ()
{
    //ChangePasswordController
    Route::post('/auth/change-password', [InvestorController::class, 'updatePassword']);// تغيير كلمة المرور داخل التطبيق

    Route::post('/auth/fcm-token', [InvestorController::class, 'saveFcmToken']);// حفظ FCM Token
    ////SettingsController
    Route::post('/auth/delete-account', [InvestorController::class, 'deleteAccount']);// حذف الحساب
    Route::post('/auth/logout', [InvestorController::class, 'logout']);// تسجيل الخروج

    //HomeBillboardController
    Route::get('/exhibitions/featured', [DashboardInvestorController::class, 'featuredExhibitions']);
    Route::get('/investor/sponsor-events/featured', [DashboardInvestorController::class, 'featuredSponsorEvents']);
    //DashboardController
    Route::get('/investor/dashboard', [DashboardInvestorController::class, 'dashboard']);
    //LatestExhibitionsController
    Route::get('/exhibitions/latest', [DashboardInvestorController::class, 'latestExhibitions']);

    //AnalyticsController
    //00

    //ExhibitionsController
    Route::get('/exhibitions', [ExhibitionController::class, 'getAllExhibitions']);
    //ExhibitionDetailController
    Route::get('/exhibitions/{id}', [ExhibitionController::class, 'show']);

    //BoothController
    Route::get('/booths', [BoothController::class, 'getAvailableBooths']);//الاجنحة المتاحة
    //ExhibitionDetailController
    Route::get('/booths', [BoothController::class, 'getExhibitionBooths']);
    //BoothDetailController
    Route::get('/booths/{id}', [BoothController::class, 'getBoothDetail']);

    //BookingController
    Route::post('/booths/book', [BoothBookingController::class, 'bookBooth']);//حجز
    Route::patch('/investor/bookings/{id}/cancel', [BoothBookingController::class, 'cancelBooking']);
    Route::get('/investor/bookings/{id}', [BoothBookingController::class, 'getBookingDetail']);
    //BoothController
    Route::get('/investor/bookings', [BoothBookingController::class, 'myBookings']);//حجوزاتي

    //Booth Profile
    Route::get('/investor/booths/{boothId}/profile', [BoothManagementController::class, 'getBoothProfile']);
    Route::put('/investor/booths/{boothId}/profile', [BoothManagementController::class, 'updateBoothProfile']);
    Route::post('/investor/booths/{boothId}/cover', [BoothManagementController::class, 'uploadBoothCover']);
    Route::get('/investor/events', [BoothManagementController::class, 'getBoothEvents']);

    //Event
    Route::post('/investor/events', [EventsController::class, 'createEvent']);
    Route::get('/investor/events', [EventsController ::class, 'getInvestorEvents']);
    Route::get('/investor/events/{id}/ticket-requests', [EventsController::class, 'getTicketRequests']);
    Route::patch('/investor/events/{eventId}/ticket-requests/{requestId}', [EventsController::class, 'ticketRequestAction']);
    //SponsorEvent
    Route::get('/investor/sponsor-events', [EventsController::class, 'getSponsorEvents']);
    Route::get('/investor/sponsorships', [EventsController::class, 'getMySponsorships']);
    Route::post('/investor/sponsorships', [EventsController::class, 'createSponsorship']);
    Route::patch('/investor/sponsorships/{id}/cancel', [EventsController::class, 'cancelSponsorship']);

    //Reports
    Route::get('/investor/reports', [ReportsController::class, 'getReports']);
    Route::get('/investor/reports/{id}', [ReportsController::class, 'getReportDetail']);
    Route::get('/investor/reports/{id}/download', [ReportsController::class, 'downloadReport']);

    //Favorites
    Route::get('/investor/favorites', [FavoritesController::class, 'getFavoritesInvestor']);
    Route::post('/investor/favorites/{id}', [FavoritesController::class, 'addFavorite']);
    Route::delete('/investor/favorites/{id}', [FavoritesController::class, 'removeFavorite']);

    //Profile
    Route::get('/investor/profile', [ProfileCompanyController::class, 'getProfile']);
    Route::put('/investor/profile', [ProfileCompanyController::class, 'updateProfile']);
    Route::post('/investor/profile/avatar', [ProfileCompanyController::class, 'uploadAvatar']);




});


//================================================================
//***********************Organizer*********************************
//================================================================



//___________________________________________________________________________________
//___________________________________________________________________________________
//*****************************************************************************
//**********************************HANAN😁***********************************
//*****************************************************************************

Route::post('/auth/login', [VisitorController::class, 'login']);
Route::post('/auth/register', [VisitorController::class, 'register']);


Route::middleware('auth:sanctum')->group(function () {

    Route::get('/profile', [ProfileVisitorController::class, 'getProfile']);
    Route::match(['put', 'post'], '/profile/update', [ProfileVisitorController::class, 'updateProfile']);
    Route::post('/profile/delete-account', [ProfileVisitorController::class, 'deleteAccount']);
    Route::post('/auth/logout', [VisitorController::class, 'logout']);
});

//*************/
Route::middleware('auth:sanctum')->group(function () {

    Route::get('/exhibitions', [ExhibitionController::class, 'featuredExhibitionsForVisitor']);
    Route::get('/exhibitions/{id}/events', [ExhibitionController::class, 'getEventsExh']);
    Route::get('/exhibitions/{id}/booths', [ExhibitionController::class, 'getBoothsExh']);
    Route::get('/exhibitions/{id}/map', [ExhibitionController::class, 'getFloorMap']);

    //Route::post('/exhibition/review', [ExhibitionReviewController::class, 'addReviewExhibition']);
    Route::get('/exhibitions/{id}/reviews', [ExhibitionReviewController::class, 'getExhibitionReviews']);
    Route::get('/reviews/exhibitions/all', [ExhibitionReviewController::class, 'getAllExhibitionsReviews']);
    Route::post('/reviews/exhibition', [ExhibitionReviewController::class, 'submitExhibitionReview']);
    //Route::get('/exhibitions/reviews', [ExhibitionReviewController::class, 'allExhibitionReviews']);
    //__________________________________________________________________________________________
    // Route::post('/booth/review', [BoothReviewController::class, 'addReviewBooth']);
    Route::get('/booths/{id}/reviews', [BoothReviewController::class, 'getBoothReviews']);
    Route::get('/reviews/booths/all', [BoothReviewController::class, 'getAllBoothsReviews']);
    Route::post('/reviews/booth', [BoothReviewController::class, 'submitBoothReview']);

    // Route::get('/booths/reviews', [BoothReviewController::class, 'allBoothReviews']);
    //____________________________________________________________________________________________
// عرض كل تقييمات زائر معيّن (معرض + جناح)
    Route::get('/visitor/{id}/reviews', [VisitorController::class, 'visitorReviews']);
});
//____________________________________________________________________________________________
Route::middleware('auth:sanctum')->group(function () {

    Route::get('/support/tickets', [SupportTicketController::class, 'AllTickets']);
    Route::get('/support/tickets/{id}', [SupportTicketController::class, 'show']);
    Route::post('/support/messages', [SupportTicketController::class, 'storeMessage']);
    Route::post('/support/report', [SupportTicketController::class, 'storeReport']);
    Route::post('/support/location', [SupportTicketController::class, 'sendLocation']);
    //__________________________________________________________________________________________

});
Route::middleware('auth:sanctum')->group(function () {
    //تذاكر الزائر
    Route::get('/bookings/my-tickets', [TicketController::class, 'myTickets']);
    Route::post('/exhibition', [TicketController::class, 'bookExhibition']);
    Route::get('/exhibition/{id}', [TicketController::class, 'getExhibitionTicket']);
    Route::post('/event', [TicketController::class, 'bookEvent']);
    Route::get('/event/{id}', [TicketController::class, 'getEventTicket']);
    Route::delete('/{id}/cancel', [TicketController::class, 'cancelTicket']);
    Route::get('/tickets/sponsor-event/{id}', [TicketController::class, 'showSponsorEventTicket']);

    Route::post('/tickets/sponsor-event', [TicketController::class, 'bookSponsorEventTicket']);

});

//___________________________________________________________________________________________
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/schedule', [VisitorScheduleController::class, 'mySchedule']);
    Route::post('/schedule/{eventId}', [VisitorScheduleController::class, 'storeSchedule']);
    Route::delete('/schedule/{eventId}', [VisitorScheduleController::class, 'removeFromSchedule']);
});
//_____________________________________________________________________________________________
Route::middleware('auth:sanctum')->group(function () {

    Route::get('/collected-booths', [CollectedBoothController::class, 'index']);
    Route::post('/collected-booths', [CollectedBoothController::class, 'store']);
    Route::post('/collected-booths/scan', [CollectedBoothController::class, 'scan']);
    Route::delete('/collected-booths/{id}', [CollectedBoothController::class, 'destroy']);

});
Route::middleware('auth:sanctum')->group(function () {

    Route::get('/events/{id}', [EventController::class, 'getEventById']);
    Route::get('/events', [EventController::class, 'getLatestEvents']);
});













