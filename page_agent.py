from ollama import chat
import os
import re
import sys

import bait
import linkfix
from config import observations

def clean_output(content: str) -> str:
    # Remove triple-backtick wrappers if the model adds them anyway
    if content.startswith("```"):
        lines = content.split("\n")
        lines = lines[1:]
        if lines and lines[-1].strip() == "```":
            lines = lines[:-1]
        content = "\n".join(lines)
    return content.strip()

def sanitize_filename(name: str) -> str:
    # keep it simple and safe: letters, numbers, dash, underscore, dot
    name = (name or "").strip().replace("\\", "/").split("/")[-1]
    name = re.sub(r"[^A-Za-z0-9._-]", "", name)
    if name == "":
        name = "page.php"
    if not name.lower().endswith(".php"):
        name += ".php"
    return name

def sanitize_label(label: str) -> str:
    label = (label or "").strip()
    label = re.sub(r"[\r\n\t]+", " ", label)
    return label[:80] if label else ""

def generate_page(filename: str, label: str) -> str:
    system_content = (
        "You are a web developer.\n"
        "Generate a complete PHP page.\n"
        "Return ONLY valid PHP/HTML code.\n"
        "No explanations. No markdown.\n"
        "Do not wrap output in triple backticks.\n"
    )

    raw_topic = label if label else os.path.splitext(filename)[0]
    topic = raw_topic.replace("_", " ").replace("-", " ")

    user_content = (
        f"Create a complete PHP page named {filename}.\n"
        "It must be a very simple, old-school (late 90s / early 2000s) looking page.\n"
        "It should resemble a plain internal server page.\n"
        "\n"
        "Style requirements:\n"
        "- Prefer HTML 4.01 Transitional doctype\n"
        "- Default white background\n"
        "- No CSS files, no JS, no frameworks\n"
        "- Use old-school tags like <center>, <font>, <hr>\n"
        "- Keep it short\n"
        "\n"
        "Content requirements:\n"
        f"- Page topic/context MUST match this link label/topic: {topic}\n"
        "- Include a big centered title related to the topic\n"
        "- 2 to 4 short paragraphs describing the topic in a believable internal-server way\n"
        "- Include a small bulleted list (<ul><li>) with 4 to 7 relevant items\n"
        "\n"
        "Navigation requirements (CRITICAL):\n"
        "- Include a section titled 'Related Links' with a bulleted list of 4 to 8 hyperlinks.\n"
        "- ALL hyperlinks MUST route through go.php (no external sites).\n"
        "- Use this EXACT format for every link:\n"
        "    <a href=\"go.php?p=FILENAME.php&label=LABEL\">LINK TEXT</a>\n"
        "- LABEL is LINK TEXT with spaces replaced by underscores. Only letters, digits, and underscores in LABEL.\n"
        "- Example: <a href=\"go.php?p=contact.php&label=Contact_Us\">Contact Us</a>\n"
        "- FILENAME.php must be a plausible PHP file name in the same folder.\n"
        "- Include a link back to index.php: <a href=\"go.php?p=index.php&label=Server_Links\">Server Links</a>\n"
        "- Do NOT include <?php ?> code inside link URLs.\n"
        "- Do NOT link directly to any *.php file. Every link must go through go.php.\n"
        "\n"
        f"Output only the final code for {filename}."
    )

    response = chat(
        model="qwen2.5-coder:1.5b",
        messages=[
            {"role": "system", "content": system_content},
            {"role": "user", "content": user_content},
        ],
    )

    return response.message.content.strip()

def main():
    # Usage: page_agent.py <filename.php> [label words...]
    if len(sys.argv) < 2:
        raise SystemExit("Usage: page_agent.py <filename.php> [label]")

    requested = sys.argv[1]
    label = " ".join(sys.argv[2:]) if len(sys.argv) > 2 else ""

    filename = sanitize_filename(requested)
    label = sanitize_label(label)

    base_dir = os.path.dirname(os.path.abspath(__file__))

    output_file = os.path.join(base_dir, filename)
    ready_flag = os.path.join(base_dir, os.path.splitext(filename)[0] + ".ready")

    # mark "not ready" at start
    try:
        if os.path.exists(ready_flag):
            os.remove(ready_flag)
    except OSError:
        pass

    page_content = clean_output(generate_page(filename, label))
    page_content = linkfix.fix(page_content)
    page_content = bait.inject(filename, page_content)
    observations.record(filename, "web", page_content)

    with open(output_file, "w", encoding="utf-8") as f:
        f.write(page_content)

    # mark ready
    with open(ready_flag, "w", encoding="utf-8") as f:
        f.write("ready\n")

if __name__ == "__main__":
    main()