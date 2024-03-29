<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\Brand;
use App\Models\MultiImg;
use App\Models\SubCategory;
use App\Models\User;
use Carbon\Carbon;
use Intervention\Image\Facades\Image;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class ProductController extends Controller
{
    public function AllProduct()
    {
        $products = Product::latest()->get();
        return view('backend.product.product_all',compact('products'));
    } // End Method

    public function AddProduct()
    {
        $activeVendor = User::where('status','active')->where('role','vendor')->latest()->get();
        $brands = Brand::latest()->get();
        $categories = Category::latest()->get();
        return view('backend.product.product_add',compact('brands','categories','activeVendor'));
    } // End Method

    public function StoreProduct(Request $request)
    {
        $image = $request->file('product_thambnail');
        $name_gen = hexdec(uniqid()).'.'.$image->getClientOriginalExtension();
        Image::make($image)->resize(800,800)->save('upload/products/thambnail/'.$name_gen);
        $save_url = 'upload/products/thambnail/'.$name_gen;

        $product_id = Product::insertGetId([

            'brand_id' => $request->brand_id,
            'category_id' => $request->category_id,
            'subcategory_id' => $request->subcategory_id,
            'product_name' => $request->product_name,
            'product_slug' => strtolower(str_replace(' ','-',$request->product_name)),

            'product_code' => $request->product_code,
            'product_qty' => $request->product_qty,
            'product_tags' => $request->product_tags,
            'product_size' => $request->product_size,
            'product_color' => $request->product_color,

            'selling_price' => $request->selling_price,
            'discount_price' => $request->discount_price,
            'short_descp' => $request->short_descp,
            'long_descp' => $request->long_descp,

            'hot_deals' => $request->hot_deals,
            'featured' => $request->featured,
            'special_offer' => $request->special_offer,
            'special_deals' => $request->special_deals,

            'product_thambnail' => $save_url,
            'vendor_id' => $request->vendor_id,
            'status' => 1,
            'created_at' => Carbon::now(),

        ]);

        /// Multiple Image Upload From her //////
        $images = $request->file('multi_img');
        foreach($images as $img){
            $make_name = hexdec(uniqid()).'.'.$img->getClientOriginalExtension();
            Image::make($img)->resize(800,800)->save('upload/products/multi-image/'.$make_name);
            $uploadPath = 'upload/products/multi-image/'.$make_name;


        MultiImg::insert([

            'product_id' => $product_id,
            'photo_name' => $uploadPath,
            'created_at' => Carbon::now(),

        ]);
        } // end foreach

        /// End Multiple Image Upload From her //////

        $notification = array(
            'message' => 'Product Inserted Successfully',
            'alert-type' => 'success'
        );

        return redirect()->route('all.product')->with($notification);
    } // End Method

    public function EditProduct($id)
    {
        $activeVendor = User::where('status','active')->where('role','vendor')->latest()->get();
        $brands = Brand::latest()->get();
        $categories = Category::latest()->get();
        $product = Product::findOrFail($id);
        $subcategories = SubCategory::where('category_id', $product->category_id)->latest()->get();
        $uploadedImages = MultiImg::where('product_id', $product->id)->get();
        return view('backend.product.product_edit',compact('brands','categories','subcategories','activeVendor','product','uploadedImages'));
    }

    public function updateProduct(Request $request, $id){
        // dd($request->all());
        $delete_image = $request->delete_image;

        foreach($delete_image as $image){
            $id = $image;

            $imageInfo = MultiImg::query()->find($id);
            $image_name = $imageInfo->photo_name;
            $imageInfo->delete();

            // Delete old image if it exists
            if(File::exists(public_path($image_name))) {
                File::delete(public_path($image_name));
            }

        }
        return redirect()->back();
    }

    // Product Active and InActive
    public function toggleProductStatus(Request $request, $status)
    {
        // Validate input
        $request->validate([
            'id' => 'required|exists:products,id',
        ]);

        $product_id = $request->id;

        // Update product status
        Product::findOrFail($product_id)->update([
            'status' => $status,
        ]);

        // Set appropriate notification message and type
        $notification = [
            'message' => ($status == 1) ? 'Product Activated Successfully' : 'Product Inactivated Successfully',
            'alert-type' => ($status == 1) ? 'success' : 'warning',
        ];

        return redirect()->route('all.product')->with($notification);
    }

    public function InactiveProductApprove(Request $request)
    {
        return $this->toggleProductStatus($request, 0);
    }

    public function ActiveProductApprove(Request $request)
    {
        return $this->toggleProductStatus($request, 1);
    }

}
