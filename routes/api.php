<?php

use App\Http\Controllers\BoothController;
use App\Http\Controllers\BoothReviewController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\CompanyProfileController;
use App\Http\Controllers\DashboardInvestorController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\ExhibitionController;
use App\Http\Controllers\ExhibitionReviewController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\SponsorshipBookingController;
use App\Http\Controllers\SponsorEventController;
use App\Http\Controllers\SupportTicketController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VisitorController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


//-----------Rrgister/Login/Logout----------
Route::post('/register', [UserController::class, 'register']);
Route::post('/login', [UserController::class, 'login']);
Route::post('/logout', [UserController::class, 'logout'])->middleware('auth:sanctum');

//----------DashboardInvestor----------
Route::get('/dashboardinv', [DashboardInvestorController::class, 'getDashboardStats']);
//___________________________________________________________________________________
Route::middleware('auth:sanctum')->group(function () {
    // Organizer routes
    Route::get('/organizer/exhibitions', [ExhibitionController::class, 'organizerIndex']);
    Route::post('/organizer/exhibitions', [ExhibitionController::class, 'store']);
    Route::put('/organizer/exhibitions/{id}', [ExhibitionController::class, 'update']);
    Route::delete('/organizer/exhibitions/{id}', [ExhibitionController::class, 'destroy']);
    Route::post('/organizer/exhibitions/{id}/archive', [ExhibitionController::class, 'archive']);

    // Investor profile routes
    Route::post('/company-profile', [CompanyProfileController::class, 'store']);
    Route::put('/company-profile', [CompanyProfileController::class, 'update']);
    Route::get('/company-profile', [CompanyProfileController::class, 'show']);

    // Investor dashboard / analytics
    Route::get('/investor/dashboard', [DashboardInvestorController::class, 'getDashboardStats']);

    // Booth booking routes
    Route::get('/investor/booths', [BoothController::class, 'myBookings']);
    Route::post('/investor/booths/{booth_id}/book', [BoothController::class, 'bookBooth']);
    Route::get('/investor/booths/{bookingId}', [BoothController::class, 'boothDetails']);

    // Event routes for investor
    Route::post('/events', [EventController::class, 'store']);
    Route::put('/events/{id}', [EventController::class, 'update']);
    Route::get('/events/{id}', [EventController::class, 'show']);
    Route::delete('/events/{id}', [EventController::class, 'destroy']);
    Route::get('/events/my-booths', [EventController::class, 'myBoothEvents']);
    Route::get('/events/my-booths/{eventId}', [EventController::class, 'showEventDetails']);
    Route::get('/events/booth/{boothId}', [EventController::class, 'boothEvents']);
    Route::get('/events/exhibition/{exhibitionId}', [EventController::class, 'exhibitionEvents']);
    Route::get('/events/my', [EventController::class, 'myEvents']);

    // Campaign routes
    Route::get('/investor/campaigns', [CampaignController::class, 'index']);
    Route::post('/investor/campaigns', [CampaignController::class, 'store']);
    Route::delete('/investor/campaigns/{id}', [CampaignController::class, 'destroy']);

    // Sponsorship booking routes
    Route::get('/investor/sponsorships', [SponsorshipBookingController::class, 'myBookings']);
    Route::post('/investor/sponsorships/{eventId}', [SponsorshipBookingController::class, 'store']);
    Route::get('/investor/sponsorships/{id}', [SponsorshipBookingController::class, 'show']);
    Route::patch('/investor/sponsorships/{id}/cancel', [SponsorshipBookingController::class, 'cancel']);
});
//___________________________________________________________________________________
Route::middleware('auth:sanctum')->group(function () {

    // قبول طلب تذكرة + توليد QR
    Route::post('/tickets/{ticketId}/approve', [TicketController::class, 'approvTicket']);

    // رفض طلب تذكرة
    Route::post('/tickets/{ticketId}/reject', [TicketController::class, 'rejectTicket']);

    // عرض التذاكر المعلقة
    Route::get('/events/{eventId}/tickets/pending', [TicketController::class, 'pendingTickets']);

    // عرض التذاكر المقبولة
    Route::get('/events/{eventId}/tickets/accepted', [TicketController::class, 'acceptedTickets']);

    // عرض التذاكر المرفوضة
    Route::get('/events/{eventId}/tickets/rejected', [TicketController::class, 'rejectedTickets']);

});
//___________________________________________________________________________________
Route::middleware('auth:sanctum')->group(function () {

    Route::post('/favorites/add', [FavoriteController::class, 'addToFavorite']);
    Route::post('/favorites/remove', [FavoriteController::class, 'removeFromFavorite']);

    Route::get('/favorites/exhibitions', [FavoriteController::class, 'favoriteExhibitions']);
    Route::get('/favorites/booths', [FavoriteController::class, 'favoriteBooths']);
    Route::get('/favorites/events', [FavoriteController::class, 'favoriteEvents']);

});

// public exhibition routes
Route::get('/exhibitions', [ExhibitionController::class, 'getAllExhibitions']);
Route::get('/exhibitions/filter', [ExhibitionController::class, 'filter']);
Route::get('/exhibitions/{id}', [ExhibitionController::class, 'show']);
Route::get('/exhibitions/ongoing', [ExhibitionController::class, 'ongoing']);
Route::get('/exhibitions/finished', [ExhibitionController::class, 'finished']);
Route::get('/exhibitions/upcoming', [ExhibitionController::class, 'upcoming']);
//___________________________________________________________________________________
Route::middleware('auth:sanctum')->group(function () {

    Route::get('/ads', [SponsorEventController::class, 'getMySponsorshipAll']);
    Route::get('/ads/{bookingId}', [SponsorEventController::class, 'showSponsorshipAdDetails']);

});
//___________________________________________________________________________________
//___________________________________________________________________________________
//*****************************************************************************
//**********************************HANAN😁***********************************
//*****************************************************************************

//التقييمات
//*************/ 
Route::middleware('auth:sanctum')->group(function () {
    // إضافة تقييم لمعرض معيّن
    Route::post('/exhibition/review', [ExhibitionReviewController::class, 'addReviewExhibition']);
    // عرض تقييمات معرض معيّن
    Route::get('/exhibition/{id}/reviews', [ExhibitionReviewController::class, 'showReviewsExhibition']);
    // عرض كل تقييمات كل المعارض
    Route::get('/exhibitions/reviews', [ExhibitionReviewController::class, 'allExhibitionReviews']);
    //___________________________________________________________________________________________
// إضافة تقييم لجناح معيّن
    Route::post('/booth/review', [BoothReviewController::class, 'addReviewBooth']);
    // عرض تقييمات جناح معيّن
    Route::get('/booth/{id}/reviews', [BoothReviewController::class, 'showReviewsBooth']);
    // عرض كل تقييمات كل الأجنحة
    Route::get('/booths/reviews', [BoothReviewController::class, 'allBoothReviews']);
    //____________________________________________________________________________________________
// عرض كل تقييمات زائر معيّن (معرض + جناح)
    Route::get('/visitor/{id}/reviews', [VisitorController::class, 'visitorReviews']);
});
//____________________________________________________________________________________________
Route::middleware('auth:sanctum')->group(function () {
    // عرض كل تذاكر الدعم الخاصة بالمستخدم
    Route::get('/support-tickets', [SupportTicketController::class, 'index']);
    // عرض تذكرة دعم واحدة
    Route::get('/support-tickets/{id}', [SupportTicketController::class, 'show']);
    // إنشاء تذكرة دعم من نوع رسالة
    Route::post('/support-tickets/message', [SupportTicketController::class, 'storeMessage']);
    // إنشاء تذكرة دعم من نوع تقرير
    Route::post('/support-tickets/report', [SupportTicketController::class, 'storeReport']);

});









