@extends('layouts.app')

@section('title', 'Inputter Dashboard')

@section('nav-links')
    <a href="{{ route('inputter.dashboard') }}" class="inline-flex items-center border-b-2 border-primary-500 px-1 pt-1 text-sm font-medium text-gray-900">
        Dashboard
    </a>
    <a href="#" class="inline-flex items-center border-b-2 border-transparent px-1 pt-1 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700">
        Securities
    </a>
    <a href="#" class="inline-flex items-center border-b-2 border-transparent px-1 pt-1 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700">
        Auction Results
    </a>
    <a href="#" class="inline-flex items-center border-b-2 border-transparent px-1 pt-1 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700">
        My Submissions
    </a>
@endsection

@section('header')
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">Inputter Dashboard</h1>
        <div class="text-sm text-gray-500">
            Last login: {{ Auth::user()->last_login_at ? Auth::user()->last_login_at->diffForHumans() : 'First time' }}
        </div>
    </div>
@endsection

@section('content')
    <!-- Stats Overview -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-8">
        <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow border border-gray-200 sm:p-6">
            <dt class="truncate text-sm font-medium text-gray-500">My Submissions</dt>
            <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900">0</dd>
        </div>

        <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow border border-gray-200 sm:p-6">
            <dt class="truncate text-sm font-medium text-gray-500">Pending Approval</dt>
            <dd class="mt-1 text-3xl font-semibold tracking-tight text-yellow-600">0</dd>
        </div>

        <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow border border-gray-200 sm:p-6">
            <dt class="truncate text-sm font-medium text-gray-500">Approved</dt>
            <dd class="mt-1 text-3xl font-semibold tracking-tight text-green-600">0</dd>
        </div>

        <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow border border-gray-200 sm:p-6">
            <dt class="truncate text-sm font-medium text-gray-500">Rejected</dt>
            <dd class="mt-1 text-3xl font-semibold tracking-tight text-red-600">0</dd>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="mb-8">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h2>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <a href="#" class="relative block rounded-lg border-2 border-dashed border-gray-300 p-6 text-center hover:border-primary-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition-colors duration-200">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                <span class="mt-2 block text-sm font-medium text-gray-900">Add Security</span>
            </a>

            <a href="#" class="relative block rounded-lg border-2 border-dashed border-gray-300 p-6 text-center hover:border-primary-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition-colors duration-200">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
                <span class="mt-2 block text-sm font-medium text-gray-900">Add Auction Result</span>
            </a>

            <a href="#" class="relative block rounded-lg border-2 border-dashed border-gray-300 p-6 text-center hover:border-primary-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition-colors duration-200">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                </svg>
                <span class="mt-2 block text-sm font-medium text-gray-900">Bulk Upload</span>
            </a>
        </div>
    </div>

    <!-- Recent Submissions -->
    <div class="bg-white shadow-sm rounded-lg border border-gray-200">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Recent Submissions</h3>
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No submissions yet</h3>
                <p class="mt-1 text-sm text-gray-500">Get started by creating a new security or auction result.</p>
                <div class="mt-6">
                    <button type="button" class="inline-flex items-center rounded-md bg-primary-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600">
                        <svg class="-ml-0.5 mr-1.5 h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                        </svg>
                        New Submission
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection
