# Analysis Proxy Pipeline

## Purpose
Generate lightweight 720p H.264 MP4 proxies for fast, analysis-friendly playback on desk and TV clients. Proxies are derived from original match videos and do not replace the original files.

## Proxy Characteristics
- Resolution: 720p (or 540p for low bandwidth)
- Bitrate: ~4–6 Mbps (controlled by CRF)
- Codec: H.264 (main profile)
- Audio: muted (analysis use)
- Flags: +faststart for instant playback

## Generation Script
Use the provided PHP script:

```
php scripts/generate_analysis_proxy.php /path/to/source.mp4 [output_path]
```
- If `output_path` is omitted, `_proxy.mp4` is appended to the source filename.

## Storage
- Store proxies alongside originals or in a dedicated folder.
- Use a clear naming convention: `match_123_proxy.mp4`.

## Regeneration
- Proxies can be regenerated if the source changes.
- Consider a background job for batch regeneration.

## Routing
- Desk/TV playback: Use proxy if available, fallback to original.
- Clips/Exports/Downloads: Use original.

## Trade-offs
- Proxies speed up analysis and reduce bandwidth.
- Originals are always retained for archival/export.
- No existing workflow is broken; this is an additive layer.

## Next Steps
- Integrate proxy detection in playback API.
- Optionally, evaluate HLS for further improvements.
