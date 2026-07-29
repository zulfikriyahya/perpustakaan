#!/bin/bash
set -uo pipefail

# Script ini HANYA menggenerate source code (docs/source-code.md).
# Struktur project (docs/structure-project.md) digenerate lewat `tree`
# via target `make genstructure` / `make gendocs`, bukan di sini.

OUT_CODE="docs/source-code.md"

mkdir -p docs

# Folder yang jadi scope SOURCE CODE (backend + frontend digabung jadi satu file)
CODE_DIRS=("app" "routes" "config" "database" "bootstrap" "resources" "public/css" "public/js")

EXCLUDE_DIRS=(
".git"
".vscode"
".idea"
"node_modules"
"vendor"
"storage"
"docs"
"scripts"
"bootstrap/cache"
"public"
)

EXCLUDE_FILES=(
"generate-docs.sh"
"source-code.md"
"structure-project.md"
# ".env"
".env.example"
".gitignore"
".yarnrc.yml"
"README.md"
"LICENSE"
"composer.lock"
"yarn.lock"
"package-lock.json"
"Makefile"
"phpunit.xml"
"artisan"
"vite.config.js"
)

BINARY_EXTS=(
jpg jpeg png gif webp svg ico bmp tiff
mp3 mp4 wav ogg webm
pdf zip tar gz rar 7z
woff woff2 ttf eot otf
xlsx xls doc docx ppt pptx
exe bin so dll dylib
db sqlite sqlite3
lock sh md
)

declare -A LANG_MAP=(
  [php]="php"
  [json]="json"
  [md]="md"
  [sh]="bash"     [bash]="bash"
  [env]="bash"
  [sql]="sql"
  [txt]="text"
  [blade]="blade"
  [ts]="ts"       [tsx]="tsx"
  [js]="js"       [jsx]="jsx"
  [css]="css"
  [yml]="yaml"    [yaml]="yaml"
  [html]="html"
  [mjs]="js"
  [xml]="xml"
)

is_binary_ext() {
  local ext="${1,,}"
  for bext in "${BINARY_EXTS[@]}"; do
    [[ "$ext" == "$bext" ]] && return 0
  done
  return 1
}

# Dicek per-segmen path, bukan prefix/substring - menghindari false positive
# seperti folder "docsomething/" ke-exclude gara-gara mengandung "docs"
is_excluded_dir() {
  local path="$1"
  IFS='/' read -ra parts <<< "$path"
  for part in "${parts[@]}"; do
    for d in "${EXCLUDE_DIRS[@]}"; do
      [[ "$part" == "$d" ]] && return 0
    done
  done
  return 1
}

is_excluded_file() {
  local basename_rel="$1"
  for excl in "${EXCLUDE_FILES[@]}"; do
    [[ "$basename_rel" == "$excl" ]] && return 0
  done
  return 1
}

get_lang() {
  local filepath="$1"
  local basename_file
  basename_file=$(basename "$filepath")
  if [[ "$basename_file" == *.blade.php ]]; then
    echo "blade"
    return
  fi
  if [[ "$basename_file" != *.* ]]; then
    echo "text"
    return
  fi
  local ext="${filepath##*.}"
  echo "${LANG_MAP[${ext,,}]:-text}"
}

write_section() {
  local filepath="$1"
  local output="$2"
  local lang
  lang=$(get_lang "$filepath")
  {
    echo ""
    echo "## $filepath"
    echo '```'"$lang"
    cat "$filepath"
    echo ""
    echo '```'
    echo "---"
  } >> "$output"
}

# Scan semua folder di CODE_DIRS, tulis ke satu file output.
# Dedup path via associative array agar overlap antar folder tidak dobel.
scan_dirs() {
  local -n dirs_ref=$1
  local output="$2"
  local count=0
  declare -A seen=()

  for base_dir in "${dirs_ref[@]}"; do
    [[ ! -e "$base_dir" ]] && continue

    while IFS= read -r -d '' file; do
      rel="${file#./}"
      [[ -n "${seen[$rel]:-}" ]] && continue

      is_excluded_dir "$rel" && continue

      basename_rel=$(basename "$rel")
      is_excluded_file "$basename_rel" && continue

      ext="${rel##*.}"
      ext_lower="${ext,,}"
      is_binary_ext "$ext_lower" && continue

      if ! file --mime-type "$file" 2>/dev/null | grep -qE "text/|application/json|application/javascript|application/xml|inode/x-empty"; then
        continue
      fi

      seen[$rel]=1
      write_section "$rel" "$output"
      (( count++ ))
    done < <(find "./$base_dir" -type f -print0 | sort -z)
  done

  echo "$count"
}

> "$OUT_CODE"
echo "# SOURCE CODE - perpustakaan" >> "$OUT_CODE"
COUNT_CODE=$(scan_dirs CODE_DIRS "$OUT_CODE")
echo "$OUT_CODE selesai dibuat - $COUNT_CODE file tercatat."
