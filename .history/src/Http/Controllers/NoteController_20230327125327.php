<?php

namespace App\_oad_repo\Http\Controllers;

use App\Models\OAD\Note;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class NoteController extends Controller
{

    public function list(Request $request)
    {
        $hash = is_array($request->form_hash) && count($request->form_hash) == 0 ? null : $request->form_hash;
        return Note::user_name()->where([
            ['assignable_id',$hash],
            ['assignable_field',$request->field_name],
            ['assignable_type_slug',$request->slug]            
        ])->orderBy('created_at','desc')->get();

    }

    public function destroy_note(Request $request)
    {
        if ($request->has('hash')) {
            $note = Note::find($request->input('hash'));
            if ($note) {
                $note->delete();

                return response()->json(['status' => 'success', 'res' => 'Note deleted']);
            }
        }

        return response()->json(['status' => 'error', 'res' => 'Note not found']);
    }
}
