@rem = '--*-Perl-*--
@echo off
if "%OS%" == "Windows_NT" goto WinNT
perl -x -S "%0" %1 %2 %3 %4 %5 %6 %7 %8 %9
goto endofperl
:WinNT
perl -x -S %0 %*
if NOT "%COMSPEC%" == "%SystemRoot%\system32\cmd.exe" goto endofperl
if %errorlevel% == 9009 echo You do not have Perl in your PATH.
if errorlevel 1 goto script_failed_so_exit_with_non_zero_val 2>nul
goto endofperl
@rem ';
#!C:\perl\bin\perl.EXE -w
#line 15
##################################################################
# Script Name: Apachehaus Binary File Verifier
# FileName: verifybins.bat
# Version 1.0    March 03, 2016
# Copyright (c)  2016,  Apache Haus
##################################################################
  use Digest::SHA1;
  my $cd=".."; my $something = sprintf("%10s"," ");
  my $FAIL = 0;
  my $chksum = $cd."/lib/.chksum";

  open(my $fh, $chksum) || die "Cannot open file: ".$chksum;
    my @content = <$fh>;
  close($fh);
  chomp @content;
  my ($j,$Pfile)=split(/=/,$content[0]);
  my ($j,$Paver)=split(/=/,$content[1]);
  if ($content[2] =~ /^LIBRESSLVERSION/) {
    my ($j,$PLver)=split(/=/,$content[2]);
  } else {
    my ($j,$Pover)=split(/=/,$content[2]);
  }
  system("CLS");
  print "\n\n  ** Apachehaus Binary File Verifier **\n\n";
  print "  Verifying files for\n";
  print "  Apache Haus Package: ".$Pfile."\n";
  print "  Apache Version:      ".$Paver."\n";
  print "  OpenSSL Version:     ".$Pover."\n\n" if $Pover;
  print "  LibreSSL Version:    ".$Plver."\n\n" if $Plver;

  my $fl = @content;
  for ($i=3;$i<$fl;$i++) {
    my ($fpath,$fhex)=split(/::/,$content[$i]);
    my $VERIFY="FAIL"; my $Msg = "";
    my $vfile .= sprintf("%-46s", $fpath);
    my $fdigest = hexfile($cd.$fpath);
    $VERIFY = $fdigest eq $fhex ? "OK" : "FAIL";
    unless ($VERIFY eq "OK") {
      unless ($fhex eq $fdigest) {
        $Msg = "SHA\n";
        $Msg .= $something."Local file: ".$fdigest."\n";
        $Msg .= $something."Database:   ".$fhex."\n";
        $Msg .= "-\n";
      }
      $Msg = "File not found" unless (-e $cd.$fpath);
    }
    print $vfile." ".$VERIFY."  ".$Msg."\n";
    $FAIL++ unless $VERIFY eq "OK";
  }

  print "\n  ** Verification Complete **\n     ";
  print $FAIL." files failed verification\n\n";
 
  print "\n  Press ENTER key to exit.";
  $junk=getc();  print "\n";
  exit;

  # char hexfile($filename);
  sub hexfile {
    (my $filename)=@_;
    open(FILE, $filename) || return ("Can't open 'File': $!");
    binmode(FILE);
    my $sha1val = Digest::SHA1->reset->addfile(*FILE)->hexdigest, " $file\n";
    close(FILE);
    return $sha1val;
  }

##################################################################
##################################################################
__END__
:endofperl
