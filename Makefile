# Pot config
POT_NAME := hercules
POT_FILE := languages/hercules.pot
POT_SOURCE := $(shell find src -name '*.php' -type f)

lint:
	make lint:php

lint\:php:
	vendor/bin/phpcs -s --extensions=php --standard=phpcs.xml src/

pot:
	xgettext --language=php \
           --add-comments=L10N \
           --keyword=__ \
           --keyword=_e \
           --keyword=_n:1,2 \
           --keyword=_x:1,2c \
           --keyword=_ex:1,2c \
           --keyword=_nx:4c,1,2 \
           --keyword=esc_attr_ \
           --keyword=esc_attr_e \
           --keyword=esc_attr_x:1,2c \
           --keyword=esc_html_ \
           --keyword=esc_html_e \
           --keyword=esc_html_x:1,2c \
           --keyword=_n_noop:1,2 \
           --keyword=_nx_noop:3c,1,2 \
           --keyword=__ngettext_noop:1,2 \
           --package-name=$(POT_NAME) \
           --from-code=UTF-8 \
           --output=$(POT_FILE) \
           $(POT_SOURCE)

	# Add Poedit information to the template file.
	cat $(POT_FILE)|perl -pe 's/8bit\\n/8bit\\n\"\n\"X-Poedit-Basepath:\
	..\\n"\n"X-Poedit-SourceCharset: UTF-8\\n"\n\
	"X-Poedit-KeywordsList: __;_e;_n:1,2;_x:1,2c;_ex:1,2c;_nx:4c,1,2;esc_attr__;esc_attr_e;esc_attr_x:1,2c;esc_html__;esc_html_e;esc_html_x:1,2c;_n_noop:1,2;_nx_noop:3c,1,2;__ngettext_noop:1,2\\n"\n\
	"X-Poedit-SearchPath-0: .\\n"\n\
	"X-Poedit-SearchPathExcluded-0: *.js\\n"\n"Plural-Forms: nplurals=2; plural=(n != 1);\\n\\n/g'|tail -n +7|tee $(POT_FILE) >/dev/null 2>/dev/null

test:
	vendor/bin/phpunit
