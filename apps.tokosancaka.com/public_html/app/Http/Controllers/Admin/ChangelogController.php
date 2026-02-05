<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\File;

class ChangelogController extends Controller
{
    public function index()
    {
        $commits = [];

        // Cek apakah folder .git ada
        if (File::exists(base_path('.git'))) {
            try {
                // Ambil 20 log terakhir dengan format: HASH|TANGGAL|AUTHOR|PESAN
                // %h = short hash, %cd = commit date, %an = author name, %s = subject
                $cmd = 'git log --pretty=format:"%h|%cd|%an|%s" --date=format:"%d %b %Y %H:%M" -n 20';

                exec($cmd, $output);

                foreach ($output as $line) {
                    $parts = explode('|', $line);
                    if (count($parts) >= 4) {
                        $commits[] = [
                            'hash'    => $parts[0],
                            'date'    => $parts[1],
                            'author'  => $parts[2],
                            'message' => $parts[3],
                        ];
                    }
                }
            } catch (\Exception $e) {
                // Silent error
            }
        }

        return view('admin.changelog.index', compact('commits'));
    }
}
