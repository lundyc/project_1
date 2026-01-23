from yt_dlp import YoutubeDL
import requests
import subprocess
import json

def get_filesize_from_url(url):
    try:
        r = requests.head(url, allow_redirects=True, timeout=10)
        size = r.headers.get("Content-Length")
        return int(size) if size else None
    except Exception:
        return None

def bytes_to_gb(n):
    if not n:
        return "?"
    return f"{n / (1024**3):.2f} GB"

def probe_video_metadata(url):
    """
    Uses ffprobe to read MP4 headers via HTTP range requests.
    Downloads only a few KB, NOT the full file.
    """
    try:
        cmd = [
            "ffprobe",
            "-v", "error",
            "-show_entries",
            "stream=codec_type,codec_name,width,height,avg_frame_rate",
            "-of", "json",
            url
        ]

        result = subprocess.run(
            cmd,
            capture_output=True,
            text=True,
            timeout=15
        )

        if result.returncode != 0:
            return None

        return json.loads(result.stdout)

    except Exception:
        return None

def inspect_veo_formats(veo_url):
    ydl_opts = {
        "quiet": True,
        "skip_download": True,
        "no_warnings": True,
    }

    with YoutubeDL(ydl_opts) as ydl:
        info = ydl.extract_info(veo_url, download=False)

    print("\n================ MATCH INFO ================\n")
    print(f"Title      : {info.get('title')}")
    print(f"Duration   : {info.get('duration')} seconds")
    print(f"Extractor  : {info.get('extractor')}")
    print(f"Uploader   : {info.get('uploader')}")
    print(f"Live       : {info.get('is_live')}")
    print(f"Webpage URL: {info.get('webpage_url')}")

    print("\n================ FORMATS ===================\n")

    for i, f in enumerate(info.get("formats", []), start=1):
        print(f"--- Format #{i} ---")

        format_id = f.get("format_id")
        ext = f.get("ext")
        url = f.get("url")

        print(f"format_id     : {format_id}")
        print(f"extension     : {ext}")
        print(f"resolution    : {f.get('width')}x{f.get('height')}")

        # ---- FILESIZE (HEAD request) ----
        size = get_filesize_from_url(url)
        print(f"filesize      : {bytes_to_gb(size)}")

        # ---- FFPROBE METADATA ----
        meta = probe_video_metadata(url)

        video_codec = None
        audio_codec = None
        fps = None

        if meta and "streams" in meta:
            for s in meta["streams"]:
                if s.get("codec_type") == "video":
                    video_codec = s.get("codec_name")
                    afr = s.get("avg_frame_rate")
                    if afr and afr != "0/0":
                        num, den = afr.split("/")
                        fps = round(int(num) / int(den), 2)
                elif s.get("codec_type") == "audio":
                    audio_codec = s.get("codec_name")

        print(f"fps           : {fps}")
        print(f"video codec   : {video_codec}")
        print(f"audio codec   : {audio_codec}")

        print(f"protocol      : {f.get('protocol')}")
        print(f"container     : {f.get('container')}")
        print(f"source        : {f.get('source_preference')}")
        print()

if __name__ == "__main__":
    match_url = "https://app.veo.co/matches/20251213-rossvale-1-4-saltcoats-8ae3733c/"
    inspect_veo_formats(match_url)
