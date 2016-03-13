TESTS = tests/
VENDOR = vendor
PHPUNIT = $(VENDOR)/phpunit/phpunit/phpunit
PHPUNIT_ARGS =

EPUBCHECK_VERSION = 4.0.1
EPUBCHECK_URL = https://github.com/IDPF/epubcheck/releases/download/v$(EPUBCHECK_VERSION)/epubcheck-$(EPUBCHECK_VERSION).zip
EPUBCHECK_JAR = $(VENDOR)/epubcheck-$(EPUBCHECK_VERSION)/epubcheck.jar

test:
	$(PHPUNIT) --exclude-group integration $(PHPUNIT_ARGS) $(TESTS)

integration-test: $(EPUBCHECK_JAR)
	EPUBCHECK_JAR=$(EPUBCHECK_JAR) $(PHPUNIT) --group integration $(PHPUNIT_ARGS) $(TESTS)

$(EPUBCHECK_JAR):
	TMPFILE=`mktemp` && \
		curl --silent --retry 3 -L $(EPUBCHECK_URL) > $$TMPFILE && \
		unzip -qq $$TMPFILE -d $(VENDOR)
