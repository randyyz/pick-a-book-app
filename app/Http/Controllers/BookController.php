<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\Book;
use App\Models\BookLibrary;
use App\Models\CartDetail;
use App\Models\CartHeader;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BookController extends BaseController
{
    public function home(){
        return view('home');
    }

    // param boleh id, boleh ISBN. ISBN might be better.
    public function bookDetail($id){
        $book = Book::whereId($id)->first();
        // book id not found.
        if($book == NULL){
            return redirect()->route('home');
        }

        $stock = BookLibrary::groupBy('bookId')
                ->selectRaw('SUM(`stock`) as total_stock')
                ->where('bookId', $id)
                ->first()->total_stock;

        // dd($stock);
        return view('bookDetail', compact('book', 'stock'));
    }

    public function addBookToCart(Request $request, $bookId){
        $userId = Auth::user()->id;
        $libraryId = $request['library'];
        if(!CartHeader::where('memberId', '=', $userId, 'and')->where('libraryId', $libraryId)->first()){
            $cartHeader = new CartHeader();
            $cartHeader->memberId = $userId;
            $cartHeader->libraryId =$libraryId;
            $cartHeader->save();
        }
        $cartHeaderId = CartHeader::where('memberId', '=', $userId, 'and')->where('libraryId', $libraryId)->first()->id;
        // dd($cartHeaderId);

        $cartDetail = new CartDetail();
        $cartDetail->cartHeaderId = $cartHeaderId;
        $cartDetail->bookId = $bookId;
        $cartDetail->save();

        return redirect()->route('cart');
    }

    public function category($name){
        $category = Category::where('name', $name)->first();
        if($category == NULL){
            return redirect()->route('home');
        }

        $books = Book::whereHas('category', function($x) use ($name){
            $x->where('name', $name);
        })->orderBy('books.id')->get();
        return view('category', compact('books', 'name'));
    }

    public function getBookDetail(){
        $userId = Auth::user()->id;
        $libraryId = Admin::where('userId', $userId)->get()->first()->libraryId;
        $books = Book::whereRelation('library', 'libraryId', $libraryId)->get();
        // dd($books);
        return view('manageBook', compact('books'));
    }
}
