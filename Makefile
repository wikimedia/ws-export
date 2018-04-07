TESTS = tests/
VENDOR = vendor
PHP = php
PHPUNIT = $(VENDOR)/phpunit/phpunit/phpunit
PHPUNIT_ARGS =
COMPOSER_PHAR = composer.phar

EPUBCHECK_VERSION = 4.0.2
EPUBCHECK_URL = https://github.com/IDPF/epubcheck/releases/download/v$(EPUBCHECK_VERSION)/epubcheck-$(EPUBCHECK_VERSION).zip
EPUBCHECK_JAR = $(VENDOR)/epubcheck-$(EPUBCHECK_VERSION)/epubcheck.jar

test: $(PHPUNIT)
	$< --exclude-group integration $(PHPUNIT_ARGS) $(TESTS)

integration-test: $(EPUBCHECK_JAR)
	EPUBCHECK_JAR=$(EPUBCHECK_JAR) $(PHPUNIT) --group integration $(PHPUNIT_ARGS) $(TESTS)

composer-test: $(COMPOSER_PHAR)
	$(PHP) $< test

install: $(COMPOSER_PHAR)
	$(PHP) $< install

update: $(COMPOSER_PHAR)
	$(PHP) $< update

$(PHPUNIT): install

$(COMPOSER_PHAR):
	curl -sS https://getcomposer.org/installer | $(PHP)

$(EPUBCHECK_JAR):
	TMPFILE=`mktemp` && \
		curl --silent --retry 3 -L $(EPUBCHECK_URL) > $$TMPFILE && \
		unzip -qq $$TMPFILE -d $(VENDOR)
