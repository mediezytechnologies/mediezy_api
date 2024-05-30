<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Category;
use Illuminate\Http\Request;

class ArticleManagementController extends Controller
{

    public function index()
    {
        $article_categories = Category::select('id', 'category_name')->get();

        return view('articles.add_article_form', compact('article_categories'));
    }

    public function getAllArticles()
    {
        $all_articles = Article::select(
            'article_id',
            'banner_image',
            'article_title',
            'author_name',
            'author_image',
            'category_id',
            'created_at',
            'main_banner'
        )
            ->get();

        return view('articles.article_list', compact('all_articles'));
    }

    public function addArticle(Request $request)
    {
        $request->validate([
            'article_title' => 'required',
            'author_name' => 'required',
            'banner_image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'author_image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'main_banner_image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'category_id' => 'required',
            'article_description' => 'required',
        ]);

        
    
        $article = new Article();
        $article->article_title = $request->input('article_title');
        $article->author_name = $request->input('author_name');
    

        $authorImage = $request->file('author_image');
        $authorImageName = time() . '.' . $authorImage->extension();
        $authorImage->move(public_path('images'), $authorImageName);
        $article->author_image = 'images/' . $authorImageName;
    
       
        $bannerImage = $request->file('banner_image');
        $bannerImageName = 'banner_' . time() . '.' . $bannerImage->extension();
        $bannerImage->move(public_path('images'), $bannerImageName);
        $article->banner_image = 'images/' . $bannerImageName;
        
        $mainBannerImage = $request->file('main_banner_image');
        $mainBannerImageName = 'main_banner_' . time() . '.' . $mainBannerImage->extension();
        $mainBannerImage->move(public_path('images'), $mainBannerImageName);
        $article->main_banner = 'images/' . $mainBannerImageName;
        
        $article->category_id = $request->input('category_id');
        $article->article_description = $request->input('article_description');
    
      
        $article->category_name = $request->input('category_name');
    
        $article->save();
    
        return redirect()->route('articles')->with('success', 'Article added successfully!');
    }
    
}
