NODE_BINDIR = ../node_modules/.bin
export PATH := $(NODE_BINDIR):$(PATH)


# Name of the generated .po files for each available locale.
LOCALE_FILES = $(shell find locale -name '*.po')

GETTEXT_JS_SOURCES = $(shell find ../src -name '*.js')
GETTEXT_VUE_SOURCES = $(shell find ../src -name '*.vue')
GETTEXT_PHP_SOURCES = $(shell find ../lib -name '*.php')

# Makefile Targets
.PHONY: clean makemessages

clean:
	rm -rf metrics.pot locale

makemessages:
	touch metrics.pot
	../node_modules/.bin/gettext-extract --attribute v-translate \
		--output=metrics.pot $(GETTEXT_VUE_SOURCES)
	xgettext --language=JavaScript --keyword=t \
		--from-code=utf-8 --join-existing --no-wrap \
		--package-name=Metrics \
		--package-version=0.0.1 \
		--output=metrics.pot $(GETTEXT_JS_SOURCES)
	xgettext --language=PHP --keyword=t --keyword=n:1,2 \
		--from-code=utf-8 --join-existing --no-wrap \
		--package-name=Metrics \
		--package-version=0.0.1 \
		--output=metrics.pot $(GETTEXT_PHP_SOURCES)

