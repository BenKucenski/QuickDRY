using System;
using System.Collections.Generic;
using System.Configuration;
using System.Linq;
using System.Text;
using System.Threading.Tasks;

namespace QuickDRY
{
    class Constants
    {
        public static String GUID;
        public static String Network;

        public static void Init()
        {
            Constants.GUID = Strings.GUID();
            Constants.Network = QuickDRY.Network.GetNetworkIPs();
        }
    }
}
