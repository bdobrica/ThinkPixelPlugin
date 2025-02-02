import configparser  # Add this import
import datetime
import logging
import os
import re
import time
from pathlib import Path

import polib
from openai import OpenAI

# Set up logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)


def extract_localizable_strings(plugin_dir):
    """Extracts translatable strings from PHP files in a WordPress plugin directory."""
    php_files = Path(plugin_dir).rglob("*.php")
    translatable_strings = {}

    pattern = re.compile(
        r"(?:__|_e|_x|_n|_nx|esc_html__|esc_html_e|esc_html_x)\s*\(\s*(['\"])(.*?)\1\s*,\s*Strings::Domain"
    )

    for php_file in php_files:
        with php_file.open("r", encoding="utf-8") as f:
            content = f.read()
            matches = pattern.findall(content)
            for match in matches:
                text = match[1].strip()
                if text and text not in translatable_strings:
                    translatable_strings[text] = php_file.relative_to(plugin_dir)

    return translatable_strings.items()


def create_pot_file(translatable_strings, project_version, output_file):
    """Creates a gettext POT file from extracted translatable strings."""
    pot = polib.POFile()
    pot.metadata = {
        "Content-Type": "text/plain; charset=UTF-8",
        "Language": "en",
        "POT-Creation-Date": datetime.datetime.utcnow().strftime("%Y-%m-%d %H:%M:%S"),
        "Project-Id-Version": project_version,
        "Report-Msgid-Bugs-To": "Bogdan Dobrica <bdobrica@gmail.com>",
        "Last-Translator": "Bogdan Dobrica <bdobrica@gmail.com>",
        "Language-Team": "English",
        "MIME-Version": "1.0",
        "Plural-Forms": "nplurals=2; plural=(n != 1);",
    }
    for text, file in translatable_strings:
        entry = polib.POEntry(msgid=text, msgstr="", comment=str(file))
        pot.append(entry)

    pot.save(output_file)
    logger.info("POT file saved: %s", output_file)


def translate_text(client, text, target_language):
    """Uses OpenAI's GPT-4o to translate text."""
    response = client.chat.completions.create(
        model="gpt-4o",
        messages=[
            {
                "role": "system",
                "content": (
                    "You are a professional translator for a software company. "
                    f"Translate the following text to {target_language}, "
                    f"keeping the formatting as close as possible."
                ),
            },
            {"role": "user", "content": text},
        ],
    )
    translation = response.choices[0].message.content.strip()
    logger.info("Translated: %s -> %s [%s]", text, translation, target_language)
    logger.info("Waiting 5 seconds before next translation...")
    time.sleep(5.0)
    return translation


def create_po_mo_files(client, pot_file, plugin_slug, language, output_dir):
    """Generates .po and .mo files for specified languages using AI translation."""
    pot = polib.pofile(pot_file)
    output_dir = Path(output_dir)
    output_dir.mkdir(parents=True, exist_ok=True)

    po_file_path = output_dir / f"{plugin_slug}-{language}.po"
    mo_file_path = output_dir / f"{plugin_slug}-{language}.mo"

    po = polib.POFile()
    po.metadata = {
        "Content-Type": "text/plain; charset=UTF-8",
        "Language": language,
    }

    for entry in pot:
        translated_text = translate_text(client, entry.msgid, language)
        po.append(polib.POEntry(msgid=entry.msgid, msgstr=translated_text))

    po.save(str(po_file_path))
    po.save_as_mofile(str(mo_file_path))
    logger.info("Generated %s and %s", po_file_path, mo_file_path)


def get_project_version(config_file):
    """Parses the .bumpversion.cfg file to get the current project version."""
    config_path = Path(config_file)
    if not config_path.exists():
        logger.error("Config file not found: %s", config_file)
        return None

    config = configparser.ConfigParser()
    config.read(config_path)

    if "bumpversion" in config and "current_version" in config["bumpversion"]:
        return config["bumpversion"]["current_version"]
    else:
        logger.error("current_version not found in config file.")
        return None


if __name__ == "__main__":
    plugin_slug = "thinkpixel-search-rag"
    plugin_directory = Path(__file__).parent.parent / plugin_slug

    if not plugin_directory.exists():
        logger.error("Plugin directory not found: %s", plugin_directory)
        exit(1)

    config_file = Path(__file__).parent.parent / ".bumpversion.cfg"
    project_version = get_project_version(config_file)
    if not project_version:
        logger.error("Failed to get project version from config file.")
        exit(1)

    output_pot_file = plugin_directory / "languages" / f"{plugin_slug}.pot"
    translation_output_dir = plugin_directory / "languages"
    languages = ["fr", "es", "de"]

    translation_output_dir.mkdir(parents=True, exist_ok=True)

    logger.info("Extracting localizable strings...")
    extracted_strings = extract_localizable_strings(plugin_directory)

    logger.info("Found %d translatable strings.", len(extracted_strings))
    create_pot_file(extracted_strings, project_version, output_pot_file)

    if not os.getenv("OPENAI_API_KEY"):
        logger.warning("OpenAI API key not found. Skipping translation.")
        exit(1)

    client = OpenAI(api_key=os.getenv("OPENAI_API_KEY"))

    logger.info("Translating and generating PO/MO files...")
    for language in languages:
        create_po_mo_files(client, output_pot_file, plugin_slug, language, translation_output_dir)
