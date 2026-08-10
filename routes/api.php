<?php

use App\Http\Controllers\BoothBookingController;
use App\Http\Controllers\BoothController;
use App\Http\Controllers\BoothManagementController;
use App\Http\Controllers\BoothReviewController;
use App\Http\Controllers\CollectedBoothController;
use App\Http\Controllers\DashboardInvestorController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\ExhibitionController;
use App\Http\Controllers\ExhibitionReviewController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\InvestorController;
use App\Http\Controllers\ProfileVisitorController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\SponsorshipBookingController;
use App\Http\Controllers\SponsorEventController;
use App\Http\Controllers\SupportTicketController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\VisitorController;
use App\Http\Controllers\VisitorScheduleController;
use Illuminate\Support\Facades\Route;

//================================================================
//***********************Investor*********************************
//================================================================
//Auth
Route::post('/investor/auth/register', [InvestorController::class, 'register']);
Route::post('/investor/auth/verify-otp', [InvestorController::class, 'verifyOtp']);
Route::post('/investor/auth/resend-otp', [InvestorController::class, 'resendOtp']);
Route::post('/investor/auth/login', [InvestorController::class, 'login']);

Route::post('/investor/auth/forgot-password', [InvestorController::class, 'forgotPassword1']);// الخطوة 1: إرسال OTP
Route::post('/investor/auth/forgot-password/verify-otp', [InvestorController::class, 'forgotPassword2']);// الخطوة 2: التحقق من OTP
Route::post('/investor/auth/reset-password', [InvestorController::class, 'resetPassword']);// الخطوة 3: تعيين كلمة مرور جديدة


Route::middleware('auth:sanctum')->group(function () {
    Route::post('/investor/auth/change-password', [InvestorController::class, 'updatePassword']);
    Route::post('/investor/auth/fcm-token', [InvestorController::class, 'saveFcmToken']);
    Route::post('/investor/auth/delete-account', [InvestorController::class, 'deleteAccount']);
    Route::post('/investor/auth/logout', [InvestorController::class, 'logout']);

    Route::post('/auth/change-password', [InvestorController::class, 'updatePassword']);// تغيير كلمة المرور داخل التطبيق
    Route::post('/auth/fcm-token', [InvestorController::class, 'saveFcmToken']);// حفظ FCM Token
    Route::post('/auth/delete-account', [InvestorController::class, 'deleteAccount']);// حذف الحساب
    Route::post('/auth/logout', [InvestorController::class, 'logout']);// تسجيل الخروج

    //Dashboard
    Route::get('/investor/exhibitions/featured', [DashboardInvestorController::class, 'featuredExhibitions']);
    Route::get('/investor/sponsor-events/featured', [DashboardInvestorController::class, 'featuredSponsorEvents']);
    Route::get('/investor/dashboard', [DashboardInvestorController::class, 'dashboard']);
    Route::get('/investor/exhibitions/latest', [DashboardInvestorController::class, 'latestExhibitions']);

    //Exhibitions
    Route::get('/investor/exhibitions', [ExhibitionController::class, 'getAllExhibitions']);
    Route::get('/investor/exhibitions/{id}', [ExhibitionController::class, 'show']);

    //Booths
    Route::get('/investor/booths', [BoothController::class, 'getAvailableBooths']);
    Route::get('/investor/exhibition/booths', [BoothController::class, 'getExhibitionBooths']);
    Route::get('/investor/booths/{id}', [BoothController::class, 'getBoothDetail']);

    //Booking
    Route::post('/booths/book', [BoothBookingController::class, 'bookBooth']);
    Route::patch('/investor/bookings/{id}/cancel', [BoothBookingController::class, 'cancelBooking']);
    Route::get('/investor/bookings/{id}', [BoothBookingController::class, 'getBookingDetail']);
    Route::get('/investor/bookings', [BoothBookingController::class, 'myBookings']);

    //Booth Profile
    Route::get('/investor/booths/{boothId}/profile', [BoothManagementController::class, 'getBoothProfile']);
    Route::post('/investor/booths/{boothId}/profile/update', [BoothManagementController::class, 'updateBoothProfile']);
    Route::post('/investor/booths/{boothId}/cover', [BoothManagementController::class, 'uploadBoothCover']);
    Route::get('/investor/booth/events', [BoothManagementController::class, 'getBoothEvents']);

    //Event
    Route::post('/investor/events', [EventController::class, 'createEvent']);//❌
    Route::get('/investor/events', [EventController::class, 'getInvestorEvents']);
    Route::get('/investor/events/{id}/ticket-requests', [EventController::class, 'getTicketRequests']);
    Route::patch('/investor/events/{eventId}/ticket-requests/{requestId}', [EventController::class, 'ticketRequestAction']);
    //SponsorEvent
    Route::get('/investor/sponsor-events', [SponsorEventController::class, 'getSponsorEvents']);
    Route::post('/investor/sponsorships', [SponsorshipBookingController::class, 'createSponsorship']);//❌
    Route::patch('/investor/sponsorships/{id}/cancel', [SponsorshipBookingController::class, 'cancelSponsorship']);
    Route::get('/investor/sponsorships', [SponsorshipBookingController::class, 'getMySponsorships']);

    //Reports
    Route::get('/investor/reports', [ReportsController::class, 'getReports']);
    Route::get('/investor/reports/{id}', [ReportsController::class, 'getReportDetail']);
    Route::get('/investor/reports/{id}/download', [ReportsController::class, 'downloadReport']);

    //Favorites
    Route::get('/investor/favorites', [FavoriteController::class, 'getFavoritesInvestor']);
    Route::post('/investor/favorites/{id}', [FavoriteController::class, 'addFavorite']);
    Route::delete('/investor/favorites/{id}', [FavoriteController::class, 'removeFavorite']);

    //Profile
    Route::get('/investor/profile', [InvestorController::class, 'getProfile']);
    Route::put('/investor/profile', [InvestorController::class, 'updateProfile']);
    Route::post('/investor/profile/avatar', [InvestorController::class, 'uploadAvatar']);


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
    //profile+logout
    Route::get('/profile', [ProfileVisitorController::class, 'getProfile']);
    Route::match(['put', 'post'], '/profile/update', [ProfileVisitorController::class, 'updateProfile']);
    Route::post('/profile/delete-account', [ProfileVisitorController::class, 'deleteAccount']);
    Route::post('/profile/change-password', [ProfileVisitorController::class, 'changePassword']);
    Route::post('/auth/logout', [VisitorController::class, 'logout']);


    //*************/

    //exh
    Route::get('/exhibitions', [ExhibitionController::class, 'featuredExhibitionsForVisitor']);
    Route::get('/exhibitions/{id}/events', [ExhibitionController::class, 'getEventsExh']);
    Route::get('/exhibitions/{id}/booths', [ExhibitionController::class, 'getBoothsExh']);
    Route::get('/exhibitions/{id}/map', [ExhibitionController::class, 'getFloorMap']);

    //exh review
    Route::get('/exhibitions/{id}/reviews', [ExhibitionReviewController::class, 'getExhibitionReviews']);
    Route::get('/reviews/exhibitions/all', [ExhibitionReviewController::class, 'getAllExhibitionsReviews']);
    Route::post('/reviews/exhibition', [ExhibitionReviewController::class, 'submitExhibitionReview']);

    //__________________________________________________________________________________________
    //booth review
    Route::get('/booths/{id}/reviews', [BoothReviewController::class, 'getBoothReviews']);
    Route::get('/reviews/booths/all', [BoothReviewController::class, 'getAllBoothsReviews']);
    Route::post('/reviews/booth', [BoothReviewController::class, 'submitBoothReview']);

    //____________________________________________________________________________________________
// عرض كل تقييمات زائر معيّن (معرض + جناح)
    Route::get('/visitor/{id}/reviews', [VisitorController::class, 'visitorReviews']);

    //____________________________________________________________________________________________

    //support ticket
    Route::get('/support/tickets', [SupportTicketController::class, 'AllTickets']);
    Route::get('/support/tickets/{id}', [SupportTicketController::class, 'show']);
    Route::post('/support/messages', [SupportTicketController::class, 'storeMessage']);
    Route::post('/support/report', [SupportTicketController::class, 'storeReport']);
    Route::post('/support/location', [SupportTicketController::class, 'sendLocation']);
    //__________________________________________________________________________________________



    //تذاكر الزائر
    Route::get('/bookings/my-tickets', [TicketController::class, 'myTickets']);
    Route::post('/bookings/exhibition', [TicketController::class, 'bookExhibition']);
    Route::get('/booking/exhibition/{id}', [TicketController::class, 'getExhibitionTicket']);
    Route::post('/bookings/event', [TicketController::class, 'bookEvent']);
    Route::get('/booking/event/{id}', [TicketController::class, 'getEventTicket']);
    Route::delete('/bookings/{id}/cancel', [TicketController::class, 'cancelTicket']);
    Route::get('/booking/sponsor-event/{id}', [TicketController::class, 'showSponsorEventTicket']);

    Route::post('/booking/sponsor-event', [TicketController::class, 'bookSponsorEventTicket']);



    //___________________________________________________________________________________________

    Route::get('/schedule', [VisitorScheduleController::class, 'mySchedule']);
    Route::post('/schedule/{eventId}', [VisitorScheduleController::class, 'storeSchedule']);
    Route::delete('/schedule/{eventId}', [VisitorScheduleController::class, 'removeFromSchedule']);

    //_____________________________________________________________________________________________


    Route::get('/collected-booths', [CollectedBoothController::class, 'index']);
    Route::post('/collected-booths', [CollectedBoothController::class, 'store']);
    Route::post('/collected-booths/scan', [CollectedBoothController::class, 'scan']);
    Route::delete('/collected-booths/{id}', [CollectedBoothController::class, 'destroy']);

    Route::get('/events/{id}', [EventController::class, 'getEventById']);
    Route::get('/events', [EventController::class, 'getLatestEvents']);
});









