from ollama import chat
import os

def generate_page():
    system_content = (
        "You are a web developer.\n"
        "Generate a complete PHP page.\n"
        "Return ONLY valid PHP/HTML code.\n"
        "No explanations. No markdown.\n"
        "Do not wrap output in triple backticks.\n"
    )

    user_content = (
        "Create a very simple, old-school (late 90s / early 2000s) looking PHP page.\n"
        "It should resemble a plain server homepage / directory index.\n"
        "\n"
        "Style requirements:\n"
        "- Prefer HTML 4.01 Transitional doctype\n"
        "- Default white background\n"
        "- No CSS files, no JS, no frameworks\n"
        "- Use old-school tags like <center>, <font>, <hr>\n"
        "- Keep it short\n"
        "\n"
        "Content requirements:\n"
        "- Big title: Server Links\n"
        "- A short line: Choose a page:\n"
        "- Then show a bulleted list of 5 to 8 hyperlinks (<ul><li><a>)\n"
        "\n"
        "Link requirements:\n"
        "- INTERNAL links only (relative links).\n"
        "- IMPORTANT: Every hyperlink MUST point to go.php with a query parameter.\n"
        "- ONLY generate links to PHP pages. The p= value MUST end with .php (never .sh, .py, no extension).\n"
        "- Do NOT link to any real scripts/files like listener.sh, handler.sh, page_agent.py, web_agent.py, clicks.log, *.ready.\n"
        "- CRITICAL: Never write links like go.php?p/anything.php (missing '='). It must be go.php?p=FILENAME.php\n"
        "- Use this exact pattern for EACH link (copy literally; only change FILENAME.php and LINK TEXT):\n"
        "    <a href=\"go.php?p=FILENAME.php&label=<?php echo urlencode('LINK TEXT'); ?>\">LINK TEXT</a>\n"
        "- LINK TEXT must exactly match the visible link text.\n"
        "- FILENAME.php must be a plausible PHP page name in the same folder.\n"
        "- Include index.php as one of the FILENAME.php values.\n"
        "- Do NOT link to any external websites/domains.\n"
        "- Do NOT use placeholders like example.com.\n"
        "- Use believable link text based on the FILENAME.php.\n"
        "\n"
        "Output only the final code for index.php."
    )

    response = chat(
        model="qwen2.5-coder:1.5b",
        messages=[
            {"role": "system", "content": system_content},
            {"role": "user", "content": user_content}
        ],
    )

    return response.message.content.strip()

def clean_output(content):
    if content.startswith("```"):
        lines = content.split("\n")
        lines = lines[1:]
        if lines and lines[-1].strip() == "```":
            lines = lines[:-1]
        content = "\n".join(lines)
    return content

def main():
    BASE_DIR = os.path.dirname(os.path.abspath(__file__))
    output_file = os.path.join(BASE_DIR, "index.php")
    ready_flag = os.path.join(BASE_DIR, "index.ready")

    # mark "not ready" at start
    try:
        if os.path.exists(ready_flag):
            os.remove(ready_flag)
    except OSError:
        pass

    page_content = clean_output(generate_page())

    with open(output_file, "w", encoding="utf-8") as f:
        f.write(page_content)

    # mark ready
    with open(ready_flag, "w", encoding="utf-8") as f:
        f.write("ready\n")

if __name__ == "__main__":
    main()