<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Department;
use App\Models\Doctor;
use Illuminate\Http\Request;

class PageController extends Controller
{
    public function home()
    {
        $departments = Department::where('is_active', true)->orderBy('sort_order')->take(6)->get();
        $doctors = Doctor::with('department')->published()->take(6)->get();

        return view('pages.home', compact('departments', 'doctors'));
    }

    public function about()
    {
        $departmentCount = Department::where('is_active', true)->count();
        $doctorCount = Doctor::published()->count();

        return view('pages.about', compact('departmentCount', 'doctorCount'));
    }

    public function departments()
    {
        $departments = Department::withCount(['doctors' => function ($q) {
            $q->published();
        }])->where('is_active', true)->orderBy('sort_order')->get();

        return view('pages.departments', compact('departments'));
    }

    public function departmentDetail(string $slug)
    {
        $department = Department::with(['doctors' => function ($q) {
            $q->published();
        }])->where('slug', $slug)->where('is_active', true)->firstOrFail();

        $otherDepartments = Department::where('id', '!=', $department->id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->take(4)
            ->get();

        return view('pages.department-detail', compact('department', 'otherDepartments'));
    }

    public function doctors(Request $request)
    {
        $departments = Department::where('is_active', true)->orderBy('name')->get();

        $query = Doctor::with('department')->published();

        if ($request->filled('department')) {
            $query->whereHas('department', function ($q) use ($request) {
                $q->where('slug', $request->department);
            });
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('specialty', 'like', "%{$search}%")
                    ->orWhere('bio', 'like', "%{$search}%");
            });
        }

        $doctors = $query->orderBy('name')->paginate(9)->withQueryString();

        return view('pages.doctors', compact('doctors', 'departments'));
    }

    public function doctorProfile(int $id)
    {
        $doctor = Doctor::with([
            'department',
            'approvedReviews' => function ($q) {
                $q->latest()->take(10);
            },
            'approvedReviews.patient',
        ])->published()->findOrFail($id);

        $relatedDoctors = Doctor::where('department_id', $doctor->department_id)
            ->published()
            ->where('id', '!=', $doctor->id)
            ->take(3)
            ->get();

        return view('pages.doctor-profile', compact('doctor', 'relatedDoctors'));
    }

    public function patientInfo()
    {
        return view('pages.patient-info');
    }

    public function services()
    {
        $departments = Department::where('is_active', true)->get();

        return view('pages.services', compact('departments'));
    }

    public function news()
    {
        $articles = Article::published()->latest('published_at')->paginate(9);

        return view('pages.news', compact('articles'));
    }

    public function newsShow(string $slug)
    {
        $article = Article::published()->where('slug', $slug)->firstOrFail();
        $related = Article::published()->where('id', '!=', $article->id)->latest('published_at')->take(3)->get();

        return view('pages.news-show', ['article' => $article, 'related' => $related, 'isPreview' => false]);
    }

    public function portalLogin()
    {
        return view('pages.portal-login');
    }

    public function privacy()
    {
        return view('pages.privacy');
    }

    public function terms()
    {
        return view('pages.terms');
    }

    public function guide()
    {
        return view('pages.guide');
    }
}
