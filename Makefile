
#
ROOT=$(HOME)/projects/bga
GAME=keyflowerrewrite
SFTP=sftp://vagabond:@1.studio.boardgamearena.com:2022

#STATS=modules/php/Stats.php
#GENSTATS=$(ROOT)/bgautil/genstats/genstats.php
WORK=work
STUBS=$(WORK)/module/table/table.game.php
TS_STUBS=src/bga-framework.d.ts
JS=modules/js/Game.js
PHPSTAN_LEVEL=5

.PHONY: build test phpstan deploy clean

build: $(JS) $(STUBS) $(STATS)

$(JS): src/*.ts src/**/*.ts tsconfig.json $(TS_STUBS)
	npm run build:ts

#$(STATS): $(GENSTATS) stats.json Makefile
#	php $(GENSTATS) $(GAME)  > $(STATS)

$(WORK):
	mkdir $(WORK)

$(TS_STUBS): bga-framework.d.ts misc/bga-fw.patch
	patch -o $(TS_STUBS) bga-framework.d.ts misc/bga-fw.patch

$(STUBS): $(WORK) _ide_helper.php Makefile
	mkdir -p $(WORK)/module/table
	perl -p -e 's/type_arg=null,/type_arg,/;' -e 's/  exit/\/\/ exit/;' -e 's/APP_GAMEMODULE_PATH = ""/APP_GAMEMODULE_PATH = "work\/"/;' -e 's/{}\(\)\;/{}\;/;' _ide_helper.php > $(STUBS)

test: build
#	phpunit --bootstrap misc/autoload.php misc --testdox

phpstan: build $(STUBS)
	phpstan --autoload-file=$(STUBS) --level=$(PHPSTAN_LEVEL) --memory-limit=1G analyse modules/php modules/php/States

deploy: test
	lftp -e 'cd $(GAME); mirror -e -R --exclude .vscode/ --exclude .git/ --exclude work/ --exclude local/ --exclude bga-framework.d.ts --exclude node_modules/ --exclude _ide_helper.php; exit' $(SFTP)

clean:
	rm -rf $(WORK) $(TS_STUBS) $(JS) $(STATS)

pull-boilerplate:
	lftp -e 'cd $(GAME); set xfer:clobber true; get _ide_helper.php; get bga-framework.d.ts; exit' $(SFTP)
